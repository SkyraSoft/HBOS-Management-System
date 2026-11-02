<#
.SYNOPSIS
    Non-Mutating Automated Test Suite for MariaDB UAT Grant-Audit Engine
.DESCRIPTION
    Tests the strict allowlist parsing logic in Test-UatGrantLines using representative
    mocked SHOW GRANTS outputs. Validates that:
    - Only global USAGE and exact run-specific database grant (with literal escaped underscores) are permitted.
    - Global administrative privileges (*.*) fail closed.
    - Role assignments fail closed.
    - Inherited privileges from PUBLIC fail closed.
    - Unexpected database grants and other run databases fail closed.
    - Wildcard grants (% or unescaped _) fail closed.
    - Unclassifiable grant statements fail closed.
    - Missing exact run grants fail closed.
    No live database connection or process execution is required.
#>

$ErrorActionPreference = "Stop"

# Dot-source lifecycle script to load Test-UatGrantLines function
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$LifecycleScript = Join-Path $ScriptDir "uat_backend_lifecycle.ps1"

# Dot-source with -Action fingerprint to avoid running any mutations
. $LifecycleScript -Action fingerprint > $null

Write-Host "`n=================================================================" -ForegroundColor Cyan
Write-Host "   HBOS UAT GRANT-AUDIT PARSER NON-MUTATING TEST SUITE           " -ForegroundColor Cyan
Write-Host "=================================================================" -ForegroundColor Cyan

$TotalTests = 0
$PassedTests = 0
$FailedTests = 0

function Run-AuditTest {
    param (
        [string]$TestName,
        [string[]]$GrantLines,
        [string]$ExpectedEscapedDb,
        [string[]]$PublicGrantLines = @(),
        [bool]$ExpectPass,
        [string]$ExpectedErrorSubstring = ""
    )

    $script:TotalTests++
    Write-Host -NoNewline "Test ${TotalTests}: $TestName ... "

    $actualPassed = $false
    $caughtMessage = ""

    try {
        $result = Test-UatGrantLines -GrantLines $GrantLines -ExpectedEscapedDb $ExpectedEscapedDb -PublicGrantLines $PublicGrantLines
        if ($result -eq $true) {
            $actualPassed = $true
        }
    } catch {
        $actualPassed = $false
        $caughtMessage = $_.Exception.Message
    }

    if ($ExpectPass -eq $actualPassed) {
        if (-not $ExpectPass -and $ExpectedErrorSubstring) {
            if ($caughtMessage.Contains($ExpectedErrorSubstring)) {
                Write-Host "PASS [Expected Failure Detected: '$ExpectedErrorSubstring']" -ForegroundColor Green
                $script:PassedTests++
            } else {
                Write-Host "FAIL" -ForegroundColor Red
                Write-Host "  Expected error containing: '$ExpectedErrorSubstring'" -ForegroundColor Yellow
                Write-Host "  Actual error: '$caughtMessage'" -ForegroundColor Yellow
                $script:FailedTests++
            }
        } else {
            Write-Host "PASS" -ForegroundColor Green
            $script:PassedTests++
        }
    } else {
        Write-Host "FAIL" -ForegroundColor Red
        Write-Host "  Expected Pass: $ExpectPass | Actual Pass: $actualPassed" -ForegroundColor Yellow
        if ($caughtMessage) {
            Write-Host "  Caught Error: $caughtMessage" -ForegroundColor Yellow
        }
        $script:FailedTests++
    }
}

$ValidRunId = "20261025_UAT01"
$TargetDb = "hbos_uat_run_$ValidRunId"
$TargetEscapedDb = $TargetDb.Replace('_', '\_')

# TEST 1: Valid Exact Run Grant + Global USAGE
Run-AuditTest -TestName "Valid exact grant with escaped underscores and global USAGE" `
    -GrantLines @(
        'GRANT USAGE ON *.* TO `hbos_uat_user`@`127.0.0.1`',
        ('GRANT ALL PRIVILEGES ON `' + $TargetEscapedDb + '`.* TO `hbos_uat_user`@`127.0.0.1`')
    ) `
    -ExpectedEscapedDb $TargetEscapedDb `
    -ExpectPass $true

# TEST 2: Valid Exact Run Grant with Granular Table Privileges
Run-AuditTest -TestName "Valid exact grant with granular DDL/DML privileges" `
    -GrantLines @(
        'GRANT USAGE ON *.* TO ''hbos_uat_user''@''127.0.0.1''',
        ('GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER ON `' + $TargetEscapedDb + '`.* TO ''hbos_uat_user''@''127.0.0.1''')
    ) `
    -ExpectedEscapedDb $TargetEscapedDb `
    -ExpectPass $true

# TEST 3: Global Privilege Breach (ALL PRIVILEGES on *.*)
Run-AuditTest -TestName "Rejection of global ALL PRIVILEGES on *.*" `
    -GrantLines @(
        'GRANT ALL PRIVILEGES ON *.* TO `hbos_uat_user`@`127.0.0.1`',
        ('GRANT ALL PRIVILEGES ON `' + $TargetEscapedDb + '`.* TO `hbos_uat_user`@`127.0.0.1`')
    ) `
    -ExpectedEscapedDb $TargetEscapedDb `
    -ExpectPass $false `
    -ExpectedErrorSubstring "Excessive global privileges on *.* detected"

# TEST 4: Global Privilege Breach (SELECT/INSERT on *.*)
Run-AuditTest -TestName "Rejection of global SELECT/INSERT on *.*" `
    -GrantLines @(
        'GRANT SELECT, INSERT ON *.* TO `hbos_uat_user`@`127.0.0.1`',
        ('GRANT ALL PRIVILEGES ON `' + $TargetEscapedDb + '`.* TO `hbos_uat_user`@`127.0.0.1`')
    ) `
    -ExpectedEscapedDb $TargetEscapedDb `
    -ExpectPass $false `
    -ExpectedErrorSubstring "Excessive global privileges on *.* detected"

# TEST 5: Database Role Assignment
Run-AuditTest -TestName "Rejection of assigned database role" `
    -GrantLines @(
        'GRANT USAGE ON *.* TO `hbos_uat_user`@`127.0.0.1`',
        'GRANT `admin_role` TO `hbos_uat_user`@`127.0.0.1`',
        ('GRANT ALL PRIVILEGES ON `' + $TargetEscapedDb + '`.* TO `hbos_uat_user`@`127.0.0.1`')
    ) `
    -ExpectedEscapedDb $TargetEscapedDb `
    -ExpectPass $false `
    -ExpectedErrorSubstring "Database role assignment detected"

# TEST 6: Inherited Broad PUBLIC Role Grant
Run-AuditTest -TestName "Rejection of broad privileges granted to PUBLIC role" `
    -GrantLines @(
        'GRANT USAGE ON *.* TO `hbos_uat_user`@`127.0.0.1`',
        ('GRANT ALL PRIVILEGES ON `' + $TargetEscapedDb + '`.* TO `hbos_uat_user`@`127.0.0.1`')
    ) `
    -ExpectedEscapedDb $TargetEscapedDb `
    -PublicGrantLines @("GRANT SELECT ON *.* TO 'PUBLIC'") `
    -ExpectPass $false `
    -ExpectedErrorSubstring "Inherited PUBLIC grant detected"

# TEST 7: Inherited PUBLIC Grant on Specific Protected Database
Run-AuditTest -TestName "Rejection of database privileges granted to PUBLIC" `
    -GrantLines @(
        'GRANT USAGE ON *.* TO `hbos_uat_user`@`127.0.0.1`',
        ('GRANT ALL PRIVILEGES ON `' + $TargetEscapedDb + '`.* TO `hbos_uat_user`@`127.0.0.1`')
    ) `
    -ExpectedEscapedDb $TargetEscapedDb `
    -PublicGrantLines @('GRANT ALL PRIVILEGES ON `hbos`.* TO ''PUBLIC''') `
    -ExpectPass $false `
    -ExpectedErrorSubstring "Inherited PUBLIC grant detected"

# TEST 8: Unexpected Protected Database Grant (hbos)
Run-AuditTest -TestName "Rejection of grant on protected production database 'hbos'" `
    -GrantLines @(
        'GRANT USAGE ON *.* TO `hbos_uat_user`@`127.0.0.1`',
        'GRANT ALL PRIVILEGES ON `hbos`.* TO `hbos_uat_user`@`127.0.0.1`',
        ('GRANT ALL PRIVILEGES ON `' + $TargetEscapedDb + '`.* TO `hbos_uat_user`@`127.0.0.1`')
    ) `
    -ExpectedEscapedDb $TargetEscapedDb `
    -ExpectPass $false `
    -ExpectedErrorSubstring "Unclassifiable or unexpected grant detected"

# TEST 9: Extraneous Grant on Different UAT Run Database
Run-AuditTest -TestName "Rejection of grant on different UAT run database" `
    -GrantLines @(
        'GRANT USAGE ON *.* TO `hbos_uat_user`@`127.0.0.1`',
        'GRANT ALL PRIVILEGES ON `hbos\_uat\_run\_OTHER`.* TO `hbos_uat_user`@`127.0.0.1`',
        ('GRANT ALL PRIVILEGES ON `' + $TargetEscapedDb + '`.* TO `hbos_uat_user`@`127.0.0.1`')
    ) `
    -ExpectedEscapedDb $TargetEscapedDb `
    -ExpectPass $false `
    -ExpectedErrorSubstring "Unclassifiable or unexpected grant detected"

# TEST 10: Multi-Character Wildcard Grant (%)
Run-AuditTest -TestName "Rejection of database-level '%' wildcard pattern" `
    -GrantLines @(
        'GRANT USAGE ON *.* TO `hbos_uat_user`@`127.0.0.1`',
        'GRANT ALL PRIVILEGES ON `hbos\_uat\_run\_%`.* TO `hbos_uat_user`@`127.0.0.1`'
    ) `
    -ExpectedEscapedDb $TargetEscapedDb `
    -ExpectPass $false `
    -ExpectedErrorSubstring "Unclassifiable or unexpected grant detected"

# TEST 11: Single-Character Wildcard via Unescaped Underscore
Run-AuditTest -TestName "Rejection of unescaped underscore wildcards in grant" `
    -GrantLines @(
        'GRANT USAGE ON *.* TO `hbos_uat_user`@`127.0.0.1`',
        'GRANT ALL PRIVILEGES ON `hbos_uat_run_20261025_UAT01`.* TO `hbos_uat_user`@`127.0.0.1`'
    ) `
    -ExpectedEscapedDb $TargetEscapedDb `
    -ExpectPass $false `
    -ExpectedErrorSubstring "Unclassifiable or unexpected grant detected"

# TEST 12: Unclassifiable Grant (Routine / Procedure / Proxy)
Run-AuditTest -TestName "Rejection of unclassifiable stored routine / procedure grant" `
    -GrantLines @(
        'GRANT USAGE ON *.* TO `hbos_uat_user`@`127.0.0.1`',
        'GRANT EXECUTE ON PROCEDURE `mysql`.`proc_test` TO `hbos_uat_user`@`127.0.0.1`',
        ('GRANT ALL PRIVILEGES ON `' + $TargetEscapedDb + '`.* TO `hbos_uat_user`@`127.0.0.1`')
    ) `
    -ExpectedEscapedDb $TargetEscapedDb `
    -ExpectPass $false `
    -ExpectedErrorSubstring "Unclassifiable or unexpected grant detected"

# TEST 13: Missing Target Database Grant (Only USAGE Present)
Run-AuditTest -TestName "Rejection when required target run database grant is missing" `
    -GrantLines @(
        'GRANT USAGE ON *.* TO `hbos_uat_user`@`127.0.0.1`'
    ) `
    -ExpectedEscapedDb $TargetEscapedDb `
    -ExpectPass $false `
    -ExpectedErrorSubstring "Missing required grant on exact run database"

Write-Host "=================================================================" -ForegroundColor Cyan
Write-Host "TEST RESULTS: Total: $TotalTests | Passed: $PassedTests | Failed: $FailedTests" -ForegroundColor $(if ($FailedTests -eq 0) { "Green" } else { "Red" })
Write-Host "=================================================================`n" -ForegroundColor Cyan

if ($FailedTests -gt 0) {
    exit 1
} else {
    exit 0
}
