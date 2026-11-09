const { app, BrowserWindow, ipcMain, shell, dialog, Tray, Menu } = require('electron');
const path = require('path');
const { spawn } = require('child_process');

let mainWindow = null;
let tray = null;
let phpServer = null;
const PHP_PORT = 8992;

function startPhpServer() {
  const phpBin = 'C:\\xampp\\php\\php.exe';
  const rootDir = path.join(__dirname, '..');

  try {
    phpServer = spawn(phpBin, ['-S', `127.0.0.1:${PHP_PORT}`, '-t', rootDir], {
      cwd: rootDir,
      env: { ...process.env, PYTHONIOENCODING: 'utf-8', PYTHONUTF8: '1' }
    });

    phpServer.stderr.on('data', (data) => {
      // PHP server log
    });
  } catch (err) {
    console.error('Failed to start PHP server:', err);
  }
}

function createWindow() {
  mainWindow = new BrowserWindow({
    width: 1200,
    height: 780,
    minWidth: 960,
    minHeight: 600,
    frame: true,
    backgroundColor: '#070d19',
    title: 'DownloadPro v1.0 [IDM Edition]',
    webPreferences: {
      preload: path.join(__dirname, 'preload.js'),
      nodeIntegration: false,
      contextIsolation: true
    }
  });

  // Remove default menu bar for clean modern desktop look
  mainWindow.setMenuBarVisibility(false);

  // Load local PHP server URL
  const appUrl = `http://127.0.0.1:${PHP_PORT}/index.php`;

  // Wait a small tick for PHP server to bind
  setTimeout(() => {
    mainWindow.loadURL(appUrl).catch(() => {
      // Retry once if needed
      setTimeout(() => mainWindow.loadURL(appUrl), 1000);
    });
  }, 400);

  mainWindow.on('closed', () => {
    mainWindow = null;
  });
}

function createTray() {
  // Setup system tray
  try {
    const iconPath = path.join(__dirname, '..', 'assets', 'icon.png');
    tray = new Tray(iconPath);
    const contextMenu = Menu.buildFromTemplate([
      { label: 'DownloadPro v1.0', enabled: false },
      { type: 'separator' },
      { label: 'Show Dashboard', click: () => { if (mainWindow) { mainWindow.show(); mainWindow.focus(); } } },
      { label: 'Add Download URL', click: () => { if (mainWindow) { mainWindow.show(); mainWindow.webContents.send('trigger-add-url'); } } },
      { type: 'separator' },
      { label: 'Exit', click: () => { app.isQuitting = true; app.quit(); } }
    ]);
    tray.setToolTip('DownloadPro - IDM Edition');
    tray.setContextMenu(contextMenu);
    tray.on('double-click', () => {
      if (mainWindow) {
        mainWindow.show();
        mainWindow.focus();
      }
    });
  } catch (e) {
    // Tray icon optional if file doesn't exist
  }
}

// IPC Handlers
ipcMain.handle('select-directory', async () => {
  const result = await dialog.showOpenDialog(mainWindow, {
    properties: ['openDirectory', 'createDirectory']
  });
  if (!result.canceled && result.filePaths.length > 0) {
    return result.filePaths[0];
  }
  return null;
});

ipcMain.handle('select-video-file', async () => {
  const result = await dialog.showOpenDialog(mainWindow, {
    properties: ['openFile'],
    filters: [
      { name: 'Video Files', extensions: ['mp4', 'mkv', 'webm', 'avi', 'mov', 'flv'] }
    ]
  });
  if (!result.canceled && result.filePaths.length > 0) {
    return result.filePaths[0];
  }
  return null;
});

ipcMain.handle('open-path', async (event, targetPath) => {
  return await shell.openPath(targetPath);
});

ipcMain.handle('show-in-folder', async (event, targetPath) => {
  shell.showItemInFolder(targetPath);
  return true;
});

app.whenReady().then(() => {
  startPhpServer();
  createWindow();
  createTray();

  app.on('activate', () => {
    if (BrowserWindow.getAllWindows().length === 0) createWindow();
  });
});

app.on('will-quit', () => {
  if (phpServer) {
    phpServer.kill();
  }
});

app.on('window-all-closed', () => {
  if (process.platform !== 'darwin') {
    if (phpServer) phpServer.kill();
    app.quit();
  }
});
