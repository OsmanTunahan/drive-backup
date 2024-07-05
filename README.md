![Image](https://user-images.githubusercontent.com/77449397/109387234-3a08eb00-7911-11eb-93e1-505c4a4246d5.png)

# Google Drive Backup Tool

**Plesk**, **cPanel**, and **CyberPanel** have automatic Google Drive backup features. However, some alternatives like **DirectAdmin** and **CWP** lack this functionality. If your hosting panel doesn't support direct Google Drive backups, you can use this tool to automate the process.

## What Does This Tool Do?
This tool zips all files in your hosting control panel's backup folder and uploads the ZIP file to Google Drive.

## How to Use?
1. Download the **backup.php** file from GitHub. [Download Here](https://github.com/OsmanTunahan/drive-backup/blob/main/backup.php)
2. Upload the file to your web server using FTP. Place it in a secure location, e.g., https://example.com/2d9ef7c2e103d9eab521d5c55eb0af6c/backup.php
3. Access the file URL in your browser to initiate the installation wizard. Follow instructions to fill in all fields and click *Submit*. Refer to [How to Configure?](#how-to-configure) if needed.
4. After submission, follow the link provided to authenticate with your Google account linked to Google Drive. Copy the authentication code and paste it into the **Google API Authorization Key** field. Click *Submit*.

![Image](https://user-images.githubusercontent.com/77449397/109388745-0b434280-791a-11eb-8174-4cb225b02191.png)

5. Upon successful installation, you will receive a *success notice* and a **Cronjob URL Address**. Add a new cronjob in your hosting control panel to schedule backups (e.g., daily or weekly). Use a command like: `curl https://example.com/2d9ef7c2e103d9eab521d5c55eb0af6c/backup.php?cron=true`

## How to Configure?
The setup wizard requires the following information:
- **Google API Client ID**
- **Google API Client Secret**
- **Google Drive Folder ID**
- **Server Backup Folder Path**

### Steps to Configure:
- **Google API Client ID** and **Google API Client Secret**: Create a Google API application in [Google API Console](https://console.developers.google.com/). Enable **Google Drive API** and set up **OAuth Consent Screen**.
  
  ![Image](https://user-images.githubusercontent.com/77449397/109389102-f7004500-791b-11eb-9c92-c3cbfc99e9f6.png)

- **Google Drive Folder ID**: Create a new folder in [Google Drive](https://drive.google.com/), get its URL, and extract the folder ID (the part after `/folders/`).

  ![Image](https://user-images.githubusercontent.com/77449397/109387940-2cedfb00-7915-11eb-9635-74c3a7cba744.png)

- **Server Backup Folder Path**: Locate the backup folder path on your web hosting control panel (e.g., `/home/reseller_username/user_backups/`). Ensure the script has access to this folder.

  ![image](https://user-images.githubusercontent.com/77449397/109387971-60c92080-7915-11eb-9ca6-553f14fd7304.png)

By following these steps, you can set up automated backups to Google Drive for hosting panels without native support.