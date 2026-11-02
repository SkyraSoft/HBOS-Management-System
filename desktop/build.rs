fn main() {
    println!("cargo:rerun-if-changed=../Frontend/dist");
    println!("cargo:rerun-if-changed=tauri.conf.json");
    tauri_build::build()
}
