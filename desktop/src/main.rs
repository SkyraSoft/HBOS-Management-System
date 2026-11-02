#![cfg_attr(
    all(not(debug_assertions), target_os = "windows"),
    windows_subsystem = "windows"
)]

#[tauri::command]
fn get_desktop_environment() -> serde_json::Value {
    serde_json::json!({
        "environment": "tauri",
        "platform": "windows",
        "app_version": "1.0.0"
    })
}

#[tauri::command]
fn exit_application(window: tauri::Window) {
    let _ = window.close();
}

#[tauri::command]
fn minimize_application(window: tauri::Window) {
    let _ = window.minimize();
}

#[tauri::command]
fn toggle_maximize(window: tauri::Window) {
    if let Ok(is_maximized) = window.is_maximized() {
        if is_maximized {
            let _ = window.unmaximize();
        } else {
            let _ = window.maximize();
        }
    }
}

fn main() {
    // Explicit build trigger ensuring procedural macro re-evaluates and embeds updated Frontend/dist
    tauri::Builder::default()
        .invoke_handler(tauri::generate_handler![
            get_desktop_environment,
            exit_application,
            minimize_application,
            toggle_maximize
        ])
        .run(tauri::generate_context!())
        .expect("error while running HBOS POS desktop application");
}
