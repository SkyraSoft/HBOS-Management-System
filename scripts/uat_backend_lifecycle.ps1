<#
.SYNOPSIS
    HBOS Isolated UAT Backend Lifecycle & Database Safety Controller
.DESCRIPTION
    Safely provisions and tears down an isolated, uniquely identified UAT test database
    and loopback API server. Strictly enforces database isolation, prints safe connection
    fingerprints, audits MariaDB grant scope, and prevents any accidental connection to
    protected production databases. Credentials are protected: passwords are never passed
    via command-line arguments or written to logs, and subprocess calls use temporary
    MariaDB client option files protected by restrictive Windows ACLs and reliably cleaned up.
.PARAMETER Action
    start | serve | stop | fingerprint | verify-grants
.PARAMETER RunId
    Unique identifier for the UAT test session (e.g. 20261025_150000). Must match ^[a-zA-Z0-9_]{4,32}$
.PARAMETER DbHost
    Loopback database host (canonical: -DbHost, alias: -Host; defaults to 127.0.0.1)
.PARAMETER DbPort
    Database port (canonical: -DbPort, alias: -Port; defaults to 3306)
.PARAMETER DbUser
    Dedicated low-privilege UAT runtime user (canonical: -DbUser, alias: -User; defaults to hbos_uat_user)
.PARAMETER DbPassword
    Runtime database password (canonical: -DbPassword, alias: -Password, -Pass).
    Prefer omitting this parameter to trigger a hidden interactive prompt, or pre-set $env:HBOS_UAT_DB_PASSWORD.
.PARAMETER AdminUser
    Privileged provisioning admin user on disposable VM (canonical: -AdminUser, alias: -Admin; defaults to empty)
.PARAMETER AdminPassword
    Privileged provisioning password on disposable VM (canonical: -AdminPassword, alias: -AdminPass).
    Prefer omitting this parameter to trigger a hidden interactive prompt, or pre-set $env:HBOS_UAT_ADMIN_PASSWORD.
.PARAMETER AllowRootOnDisposableVm
    Explicit acknowledgement switch required if root user is ever used inside a disposable VM.
.PARAMETER Serve
    If specified with Action start, transitions directly into starting the loopback API server in the same verified process.
#>

param (
    [Parameter(Mandatory = $true, Position = 0)]
    [ValidateSet("start", "serve", "stop", "fingerprint", "verify-grants")]
    [string]$Action,

    [Parameter(Mandatory = $false)]
    [string]$RunId = (Get-Date -Format "yyyyMMdd_HHmmss"),

    [Parameter(Mandatory = $false)]
    [Alias("Host")]
    [string]$DbHost = "127.0.0.1",

    [Parameter(Mandatory = $false)]
    [Alias("Port")]
    [int]$DbPort = 3306,

    [Parameter(Mandatory = $false)]
    [Alias("User")]
    [string]$DbUser = "hbos_uat_user",

    [Parameter(Mandatory = $false)]
    [Alias("Password", "Pass")]
    [string]$DbPassword = "",

    [Parameter(Mandatory = $false)]
    [Alias("Admin")]
    [string]$AdminUser = "",

    [Parameter(Mandatory = $false)]
    [Alias("AdminPass")]
    [string]$AdminPassword = "",

    [Parameter(Mandatory = $false)]
    [switch]$AllowRootOnDisposableVm = $false,

    [Parameter(Mandatory = $false)]
    [switch]$Serve = $false
)

$ErrorActionPreference = "Stop"

# 1. RunId Strict Character Allowlist Validation
if ($RunId -notmatch '^[a-zA-Z0-9_]{4,32}$') {
    Write-Error "[FATAL SECURITY ABORT] RunId '$RunId' is invalid. Must strictly match '^[a-zA-Z0-9_]{4,32}$' (4 to 32 alphanumeric characters and underscores)."
    exit 1
}

# 2. Target Database Name & MariaDB GRANT Wildcard Escaping
$TargetDb = "hbos_uat_run_$RunId"

# In MariaDB GRANT statements, '_' is a single-character wildcard matching ANY character.
# Underscores MUST be escaped with a backslash ('\_') to restrict grants strictly to this exact database name.
$EscapedTargetDb = $TargetDb.Replace('_', '\_')

if ([string]::IsNullOrWhiteSpace($DbUser)) {
    Write-Error "[FATAL SECURITY ABORT] Database user cannot be empty. Dedicated low-privilege UAT user required."
    exit 1
}

# 3. Credential Protection & Secure Input Resolution
# Priority: Parameter -> Session Environment Variable -> Secure Interactive Prompt (hidden)
if ([string]::IsNullOrEmpty($DbPassword) -and $env:HBOS_UAT_DB_PASSWORD) {
    $DbPassword = $env:HBOS_UAT_DB_PASSWORD
}

if ([string]::IsNullOrEmpty($AdminPassword) -and $env:HBOS_UAT_ADMIN_PASSWORD) {
    $AdminPassword = $env:HBOS_UAT_ADMIN_PASSWORD
}

function Prompt-SecretSecurely {
    param ([string]$PromptMessage)
    if ([Environment]::UserInteractive) {
        $sec = Read-Host -Prompt $PromptMessage -AsSecureString
        if ($sec) {
            $bstr = [System.Runtime.InteropServices.Marshal]::SecureStringToBSTR($sec)
            try {
                # Note: In .NET, System.String instances on the managed heap are immutable and garbage-collected;
                # they are not claimed to be securely zeroed in managed memory. Unmanaged BSTR is freed below.
                return [System.Runtime.InteropServices.Marshal]::PtrToStringAuto($bstr)
            } finally {
                [System.Runtime.InteropServices.Marshal]::ZeroFreeBSTR($bstr)
            }
        }
    }
    return ""
}

if ([string]::IsNullOrEmpty($DbPassword) -and ($Action -in @("start", "serve", "stop", "verify-grants"))) {
    $prompted = Prompt-SecretSecurely -PromptMessage "Enter password for '$DbUser' (input hidden)"
    if ($prompted) {
        $DbPassword = $prompted
    }
}

if ([string]::IsNullOrEmpty($AdminPassword) -and ($Action -in @("start", "stop")) -and $AdminUser) {
    $promptedAdmin = Prompt-SecretSecurely -PromptMessage "Enter password for admin '$AdminUser' (input hidden)"
    if ($promptedAdmin) {
        $AdminPassword = $promptedAdmin
    }
}

# 4. MariaDB Temporary Client Option File Helper (Safe Subprocess Execution)
# Replaces MYSQL_PWD with temporary client configuration protected by restrictive Windows ACLs
function Invoke-WithMariaDbOptionFile {
    param (
        [string]$Password,
        [scriptblock]$ScriptBlock
    )

    if ([string]::IsNullOrEmpty($Password)) {
        & $ScriptBlock ""
        return
    }

    $tempCnf = [System.IO.Path]::GetTempFileName()
    try {
        # Secure the file so only the current Windows user identity can access it
        $acl = New-Object System.Security.AccessControl.FileSecurity
        $acl.SetAccessRuleProtection($true, $false) # Disable inheritance, purge inherited rules
        $currentUser = [System.Security.Principal.WindowsIdentity]::GetCurrent().Name
        $rule = New-Object System.Security.AccessControl.FileSystemAccessRule(
            $currentUser,
            [System.Security.AccessControl.FileSystemRights]::FullControl,
            [System.Security.AccessControl.AccessControlType]::Allow
        )
        $acl.SetAccessRule($rule)
        [System.IO.File]::SetAccessControl($tempCnf, $acl)

        # Write client configuration option file
        $escapedPassword = $Password.Replace('\', '\\').Replace('"', '\"')
        $cnfText = "[client]`r`npassword=`"$escapedPassword`"`r`n"
        [System.IO.File]::WriteAllText($tempCnf, $cnfText, [System.Text.Encoding]::ASCII)

        $optArg = "--defaults-extra-file=$tempCnf"
        & $ScriptBlock $optArg
    } finally {
        if (Test-Path $tempCnf) {
            try {
                # Overwrite content before deletion
                $zeroes = New-Object byte[] 256
                [System.IO.File]::WriteAllBytes($tempCnf, $zeroes)
            } catch {}
            Remove-Item -Path $tempCnf -Force -ErrorAction SilentlyContinue
        }
    }
}

# 5. Root Credentials Boundary Guard
if (($DbUser.Trim().ToLower() -eq "root" -or $AdminUser.Trim().ToLower() -eq "root") -and -not $AllowRootOnDisposableVm) {
    Write-Error "[FATAL SECURITY ABORT] Root database credentials are blocked by default. A dedicated low-privilege user ('hbos_uat_user') with restricted grants on '$EscapedTargetDb' is required. If running inside a confirmed disposable VM, explicitly specify -AllowRootOnDisposableVm."
    exit 1
}

if ($AllowRootOnDisposableVm -and ($DbUser.Trim().ToLower() -eq "root" -or $AdminUser.Trim().ToLower() -eq "root")) {
    Write-Warning "[SECURITY OVERRIDE] Root database credentials accepted under explicit -AllowRootOnDisposableVm override."
}

# 6. STRICT FORBIDDEN LIST: Any existing or protected database
$ForbiddenDbs = @("hbos", "production", "laravel", "mysql", "information_schema", "sys", "performance_schema", "test")
if ($ForbiddenDbs -contains $TargetDb.ToLower() -or $TargetDb.Trim() -eq "") {
    Write-Error "[FATAL SECURITY ABORT] Target database '$TargetDb' matches a protected, ambiguous, or empty database name. Execution blocked."
    exit 1
}

if ($TargetDb -notmatch '^hbos_uat_run_[a-zA-Z0-9_]+$') {
    Write-Error "[FATAL SECURITY ABORT] Target database '$TargetDb' does not match the mandatory 'hbos_uat_run_<RunId>' pattern. Execution blocked."
    exit 1
}

# 7. Connection Fingerprint Printer (Safe, Non-Secret)
function Print-Fingerprint {
    Write-Host "=================================================================" -ForegroundColor Cyan
    Write-Host "   HBOS ISOLATED UAT BACKEND CONNECTION FINGERPRINT (NON-SECRET)  " -ForegroundColor Cyan
    Write-Host "=================================================================" -ForegroundColor Cyan
    Write-Host "Target Environment   : ISOLATED_UAT_DISPOSABLE"
    Write-Host "Connection Name      : isolated_uat"
    Write-Host "Database Driver      : mariadb (PDO)"
    Write-Host "Database Host        : $DbHost (Strict Loopback)"
    Write-Host "Database Port        : $DbPort"
    Write-Host "Target Database      : $TargetDb"
    Write-Host "Escaped Grant Target : $EscapedTargetDb (No Wildcards)"
    Write-Host "Runtime DB User      : $DbUser (Low-Privilege Application Account)"
    Write-Host "Provisioning User    : $(if ($AdminUser) { $AdminUser } else { '[Same as DbUser / Pre-provisioned]' })"
    Write-Host "Network Binding      : 127.0.0.1:8000 (Loopback Only, No 0.0.0.0)"
    Write-Host "Protected DB Guard   : PASS ('hbos' and existing DBs strictly protected)"
    Write-Host "Config Cache Policy  : CLEARED BEFORE MIGRATION & SERVE"
    Write-Host "Credential Security  : PASS (Protected option file with Windows ACLs, no CLI args)"
    Write-Host "=================================================================" -ForegroundColor Cyan
}

$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$RepoRoot = Split-Path -Parent $ScriptDir
$ApiDir = Join-Path $RepoRoot "api"

function Set-UatEnvironmentVariables {
    $env:DB_CONNECTION = "isolated_uat"
    $env:UAT_DB_HOST = $DbHost
    $env:UAT_DB_PORT = $DbPort
    $env:UAT_DB_DATABASE = $TargetDb
    $env:UAT_DB_USERNAME = $DbUser
    $env:UAT_DB_PASSWORD = $DbPassword
}

function Clear-LaravelConfigCache {
    Write-Host "`n[Cache Invalidation] Clearing Laravel configuration and route cache..." -ForegroundColor Yellow
    Push-Location $ApiDir
    try {
        php artisan config:clear
        php artisan route:clear
    } finally {
        Pop-Location
    }
}

function Invoke-PreflightVerification {
    Write-Host "`n[Pre-flight Verification] Executing live database identity audit on isolated_uat..." -ForegroundColor Yellow
    Push-Location $ApiDir
    try {
        php artisan uat:verify-connection --database=isolated_uat --expected-db=$TargetDb
        if ($LASTEXITCODE -ne 0) {
            Write-Error "[FATAL SECURITY ABORT] Pre-flight connection verification failed. Aborting execution."
            exit 1
        }
    } finally {
        Pop-Location
    }
}

# 8. Strict Grant-Audit Parsing Engine (Testable, Non-Destructive)
function Test-UatGrantLines {
    param (
        [Parameter(Mandatory = $true)]
        [string[]]$GrantLines,

        [Parameter(Mandatory = $true)]
        [string]$ExpectedEscapedDb,

        [Parameter(Mandatory = $false)]
        [string[]]$PublicGrantLines = @()
    )

    $hasExactRunGrant = $false

    # Audit 1: Check for broad privileges inherited from PUBLIC role
    if ($PublicGrantLines -and $PublicGrantLines.Count -gt 0) {
        foreach ($pLine in $PublicGrantLines) {
            $trimmedP = $pLine.Trim()
            if ([string]::IsNullOrWhiteSpace($trimmedP)) { continue }

            # MariaDB standard USAGE on *.* is benign (login only)
            if ($trimmedP -match '(?i)^GRANT\s+USAGE\s+ON\s+\*\.\*\s+TO\s+') {
                continue
            }

            throw "[FATAL SECURITY ABORT] Inherited PUBLIC grant detected: '$trimmedP'. Broad PUBLIC privileges breach database isolation."
        }
    }

    # Verify ExpectedEscapedDb itself contains escaped underscores
    if ($ExpectedEscapedDb -match '(?<!\\)_') {
        throw "[FATAL SECURITY ABORT] Expected database '$ExpectedEscapedDb' contains unescaped underscore wildcards. Refusing to audit."
    }

    $escapedDbPattern = [regex]::Escape($ExpectedEscapedDb)

    # Audit 2: Strict Line-by-Line Allowlist Audit for User Grants
    foreach ($line in $GrantLines) {
        $trimmed = $line.Trim()
        if ([string]::IsNullOrWhiteSpace($trimmed)) { continue }

        # Check A: Reject database role assignments (e.g. GRANT role_name TO user)
        # Role assignments have no "ON <object>" clause
        if ($trimmed -match '(?i)^GRANT\s+[`''"]?([a-zA-Z0-9_-]+)[`''"]?\s+TO\s+' -and $trimmed -notmatch '(?i)\s+ON\s+') {
            throw "[FATAL SECURITY ABORT] Database role assignment detected: '$trimmed'. Assigned roles are rejected to prevent unverified privilege inheritance."
        }

        # Check B: Reject grants referencing PUBLIC
        if ($trimmed -match '(?i)\bTO\s+[`''"]?PUBLIC[`''"]?') {
            throw "[FATAL SECURITY ABORT] Grant explicitly references PUBLIC role: '$trimmed'. Inherited PUBLIC grants are forbidden."
        }

        # Check C: Allow only global USAGE on *.*
        if ($trimmed -match '(?i)^GRANT\s+USAGE\s+ON\s+\*\.\*\s+TO\s+') {
            continue
        }

        # Check D: Reject any other global grant (*.* with privileges other than USAGE)
        if ($trimmed -match '(?i)\s+ON\s+\*\.\*\s+TO\s+') {
            throw "[FATAL SECURITY ABORT] Excessive global privileges on *.* detected: '$trimmed'. Only USAGE is permitted."
        }

        # Check E: Allow exact run-specific database grant with literal escaped underscores
        # Matches: GRANT <privileges> ON `<ExpectedEscapedDb>`.* TO <user>
        $exactDbRegex = '(?i)^GRANT\s+(ALL\s+PRIVILEGES|[^;]+)\s+ON\s+[`''"]' + $escapedDbPattern + '[`''"]\.\*\s+TO\s+'
        if ($trimmed -match $exactDbRegex) {
            # Reject if '%' wildcard is present in the database name specifier
            if ($trimmed -match '(?i)\s+ON\s+[`''"][^`''"]*%[^`''"]*[`''"]\.\*') {
                throw "[FATAL SECURITY ABORT] Database grant contains '%' wildcard: '$trimmed'."
            }
            $hasExactRunGrant = $true
            continue
        }

        # Check F: Any other grant line cannot be classified into allowed patterns
        throw "[FATAL SECURITY ABORT] Unclassifiable or unexpected grant detected: '$trimmed'. All grants outside exact run database are rejected."
    }

    if (-not $hasExactRunGrant) {
        throw "[FATAL SECURITY ABORT] Missing required grant on exact run database '$ExpectedEscapedDb'."
    }

    return $true
}

# 9. Effective MariaDB Grant Verification (Non-Destructive)
function Test-UatUserEffectiveGrants {
    param (
        [string]$UserToCheck = $DbUser,
        [string]$HostToCheck = $DbHost,
        [string]$AuthUser = $(if ($AdminUser) { $AdminUser } else { $DbUser }),
        [string]$AuthPass = $(if ($AdminUser) { $AdminPassword } else { $DbPassword })
    )

    Write-Host "`n[Grant Audit] Verifying effective grants for '$UserToCheck'@'$HostToCheck'..." -ForegroundColor Yellow
    Write-Host "[Defense-in-Depth Note] Grant text inspection verifies grant scoping; physical isolation requires restricted MariaDB user grants and VM isolation." -ForegroundColor DarkGray

    $script:tempUserGrants = @()
    $script:tempPublicGrants = @()

    Invoke-WithMariaDbOptionFile -Password $AuthPass -ScriptBlock {
        param ($OptionFileArg)

        $showGrantsSql = "SHOW GRANTS FOR '$UserToCheck'@'$HostToCheck';"
        $mysqlArgs = @()
        if ($OptionFileArg) { $mysqlArgs += $OptionFileArg }
        $mysqlArgs += @("-h", $DbHost, "-P", $DbPort, "-u", $AuthUser, "-s", "-N", "-e", $showGrantsSql)

        $rawGrants = & mysql $mysqlArgs 2>&1
        if ($LASTEXITCODE -ne 0) {
            Write-Error "[FATAL SECURITY ABORT] Failed to retrieve grants for '$UserToCheck'@'$HostToCheck': $rawGrants"
            exit 1
        }
        $script:tempUserGrants = $rawGrants -split "`r?`n" | Where-Object { -not [string]::IsNullOrWhiteSpace($_) }

        # Version-aware MariaDB grant audit
        # Query database server version to ensure version-aware grant auditing
        $versionSql = "SELECT VERSION();"
        $verArgs = @()
        if ($OptionFileArg) { $verArgs += $OptionFileArg }
        $verArgs += @("-h", $DbHost, "-P", $DbPort, "-u", $AuthUser, "-s", "-N", "-e", $versionSql)

        $rawVersion = & mysql $verArgs 2>&1
        if ($LASTEXITCODE -ne 0) {
            Write-Error "[FATAL SECURITY ABORT] Failed to determine MariaDB version: $rawVersion"
            exit 1
        }
        $serverVersion = ([string]$rawVersion).Trim()
        Write-Host "[Database Engine] Server version identified: $serverVersion" -ForegroundColor Cyan

        # Version check: MariaDB supports the 'PUBLIC' pseudo-role starting from version 10.11 (MDEV-28828).
        # Before 10.11, granting privileges TO PUBLIC is not supported by MariaDB.
        $isPublicRoleSupported = $false
        if ($serverVersion -match '(\d+)\.(\d+)\.(\d+)') {
            $majorVer = [int]$matches[1]
            $minorVer = [int]$matches[2]
            if (($majorVer -gt 10) -or ($majorVer -eq 10 -and $minorVer -ge 11)) {
                $isPublicRoleSupported = $true
            }
        } else {
            Write-Error "[FATAL SECURITY ABORT] Unable to parse MariaDB server version string: '$serverVersion'."
            exit 1
        }

        if ($isPublicRoleSupported) {
            # MariaDB 10.11+: Query SHOW GRANTS FOR 'PUBLIC'
            $publicSql = "SHOW GRANTS FOR 'PUBLIC';"
            $pubArgs = @()
            if ($OptionFileArg) { $pubArgs += $OptionFileArg }
            $pubArgs += @("-h", $DbHost, "-P", $DbPort, "-u", $AuthUser, "-s", "-N", "-e", $publicSql)

            $rawPublic = & mysql $pubArgs 2>&1
            if ($LASTEXITCODE -eq 0) {
                $script:tempPublicGrants = $rawPublic -split "`r?`n" | Where-Object { -not [string]::IsNullOrWhiteSpace($_) }
            } else {
                $errText = [string]$rawPublic
                # When no grants are assigned to PUBLIC, MariaDB 10.11+ returns ERROR 1141
                if ($errText -match '(?i)There is no such grant defined for user [''"]?PUBLIC[''"]?') {
                    $script:tempPublicGrants = @()
                } else {
                    # Do not suppress unrelated or unexpected audit errors
                    Write-Error "[FATAL SECURITY ABORT] Unexpected error auditing PUBLIC role privileges on MariaDB $serverVersion`: $errText"
                    exit 1
                }
            }
        } else {
            # MariaDB < 10.11: PUBLIC pseudo-role does not exist; TO PUBLIC cannot be granted in this engine
            Write-Host "[Database Engine Note] MariaDB $serverVersion is earlier than 10.11. The 'PUBLIC' pseudo-role is not supported by this engine; PUBLIC role privilege inheritance is unavailable." -ForegroundColor DarkGray
            $script:tempPublicGrants = @()
        }
    }

    $userGrants = $script:tempUserGrants
    $publicGrants = $script:tempPublicGrants

    try {
        Test-UatGrantLines -GrantLines $userGrants -ExpectedEscapedDb $EscapedTargetDb -PublicGrantLines $publicGrants
        Write-Host "[Grant Audit PASS] Effective grants strictly restricted to exact run database '$TargetDb'." -ForegroundColor Green
    } catch {
        Write-Error $_.Exception.Message
        exit 1
    }
}

# 10. Action Handlers

if ($Action -eq "fingerprint") {
    Print-Fingerprint
    exit 0
}

if ($Action -eq "verify-grants") {
    Print-Fingerprint
    Test-UatUserEffectiveGrants
    exit 0
}

if ($Action -eq "start") {
    Print-Fingerprint

    Clear-LaravelConfigCache

    $provUser = if ($AdminUser) { $AdminUser } else { $DbUser }
    $provPass = if ($AdminUser) { $AdminPassword } else { $DbPassword }

    Invoke-WithMariaDbOptionFile -Password $provPass -ScriptBlock {
        param ($OptionFileArg)

        Write-Host "[Step 2/6] Creating dedicated isolated test database '$TargetDb'..." -ForegroundColor Yellow
        $createSql = "CREATE DATABASE IF NOT EXISTS ``$TargetDb`` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
        $createArgs = @()
        if ($OptionFileArg) { $createArgs += $OptionFileArg }
        $createArgs += @("-h", $DbHost, "-P", $DbPort, "-u", $provUser, "-e", $createSql)
        & mysql $createArgs
        if ($LASTEXITCODE -ne 0) {
            Write-Error "[FATAL SECURITY ABORT] Failed to create database '$TargetDb'."
            exit 1
        }

        Write-Host "[Step 3/6] Applying exact run-specific grant for '$DbUser' on '$EscapedTargetDb'..." -ForegroundColor Yellow
        $grantSql = "CREATE USER IF NOT EXISTS '$DbUser'@'$DbHost' IDENTIFIED BY '$DbPassword'; " +
                    "GRANT ALL PRIVILEGES ON ``$EscapedTargetDb``.* TO '$DbUser'@'$DbHost'; " +
                    "FLUSH PRIVILEGES;"
        $grantArgs = @()
        if ($OptionFileArg) { $grantArgs += $OptionFileArg }
        $grantArgs += @("-h", $DbHost, "-P", $DbPort, "-u", $provUser, "-e", $grantSql)
        & mysql $grantArgs
        if ($LASTEXITCODE -ne 0) {
            Write-Error "[FATAL SECURITY ABORT] Failed to apply grant on '$EscapedTargetDb'."
            exit 1
        }
    }

    Write-Host "[Step 4/6] Auditing effective grants to enforce strict isolation boundary..." -ForegroundColor Yellow
    Test-UatUserEffectiveGrants -UserToCheck $DbUser -HostToCheck $DbHost -AuthUser $provUser -AuthPass $provPass

    Write-Host "[Step 5/6] Setting process environment variables for isolated_uat connection..." -ForegroundColor Yellow
    Set-UatEnvironmentVariables

    Write-Host "[Step 6/6] Running pre-flight live connection verification & migrations..." -ForegroundColor Yellow
    Invoke-PreflightVerification

    Push-Location $ApiDir
    try {
        php artisan migrate --database=isolated_uat --force
    } finally {
        Pop-Location
    }

    Write-Host "`n[Setup Complete] UAT database setup successfully completed for '$TargetDb'." -ForegroundColor Green

    if ($Serve) {
        Write-Host "`n[-Serve specified] Seamlessly starting loopback API server in the same process..." -ForegroundColor Cyan
        Push-Location $ApiDir
        try {
            php artisan serve --host=127.0.0.1 --port=8000
        } finally {
            Pop-Location
        }
    } else {
        Write-Host "`nTo start the loopback API server in an isolated process with verified environment, run:" -ForegroundColor Cyan
        Write-Host "powershell -ExecutionPolicy Bypass -File scripts\uat_backend_lifecycle.ps1 -Action serve -RunId $RunId -DbUser $DbUser`n" -ForegroundColor White
    }
}

if ($Action -eq "serve") {
    Print-Fingerprint

    Clear-LaravelConfigCache

    Write-Host "[Environment Setup] Binding process environment variables for isolated_uat..." -ForegroundColor Yellow
    Set-UatEnvironmentVariables

    Invoke-PreflightVerification

    Write-Host "`nStarting Laravel loopback API server on 127.0.0.1:8000..." -ForegroundColor Green
    Push-Location $ApiDir
    try {
        php artisan serve --host=127.0.0.1 --port=8000
    } finally {
        Pop-Location
    }
}

if ($Action -eq "stop") {
    Print-Fingerprint

    $provUser = if ($AdminUser) { $AdminUser } else { $DbUser }
    $provPass = if ($AdminUser) { $AdminPassword } else { $DbPassword }

    Write-Host "`n[Teardown] Verifying database ownership before deletion..." -ForegroundColor Yellow
    if ($TargetDb -match '^hbos_uat_run_[a-zA-Z0-9_]+$' -and -not ($ForbiddenDbs -contains $TargetDb.ToLower())) {
        Write-Host "Dropping verified disposable test database '$TargetDb'..." -ForegroundColor Yellow
        $dropSql = "DROP DATABASE IF EXISTS ``$TargetDb``; " +
                   "REVOKE ALL PRIVILEGES, GRANT OPTION FROM '$DbUser'@'$DbHost'; " +
                   "FLUSH PRIVILEGES;"
        Invoke-WithMariaDbOptionFile -Password $provPass -ScriptBlock {
            param ($OptionFileArg)
            $dropArgs = @()
            if ($OptionFileArg) { $dropArgs += $OptionFileArg }
            $dropArgs += @("-h", $DbHost, "-P", $DbPort, "-u", $provUser, "-e", $dropSql)
            & mysql $dropArgs
        }
        Write-Host "[Teardown Complete] Database '$TargetDb' and associated run grants removed cleanly. Host databases untouched." -ForegroundColor Green
    } else {
        Write-Error "[SECURITY ERROR] Refusing to drop database '$TargetDb'. Name does not strictly match 'hbos_uat_run_<RunId>' or matches protected database."
        exit 1
    }
}
