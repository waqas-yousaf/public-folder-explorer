# Public Folder Explorer for WordPress

[![Version](https://img.shields.io/wordpress/plugin/v/public-folder-explorer)](https://wordpress.org/plugins/public-folder-explorer/)
[![Downloads](https://img.shields.io/wordpress/plugin/dt/public-folder-explorer)](https://wordpress.org/plugins/public-folder-explorer/)
[![License](https://img.shields.io/badge/License-GPLv2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Tested WP](https://img.shields.io/wordpress/v/public-folder-explorer)](https://wordpress.org/plugins/public-folder-explorer/)

This WordPress plugin allows you to display a frontend file explorer for a designated public folder on your server. Visitors can browse through the folder structure and download publicly accessible files directly from your website.

## Features

* **Frontend File Browsing:** Display a user-friendly file explorer on any WordPress page or post using the `[public_folder_explorer]` shortcode.
* **Directory Navigation:** Users can easily navigate through subdirectories within the specified public folder.
* **File Downloading:** Direct download links are provided for all allowed file types.
* **File Information:** Displays file names, sizes, and last modified dates.
* **Configurable Base Directory:** Specify the root public folder in the WordPress admin settings.
* **Allowed File Types:** Define which file extensions are allowed to be displayed and downloaded.
* **Hidden Folders and Files:** Option to hide specific folders and files from the frontend explorer.
* **Frontend Title:** Customize the title of the file explorer displayed to users.
* **Layout Options:** Supports list and grid views for file and folder display (user preference is saved).
* **Search Functionality:** Allows users to search for specific files and folders within the public directory.
* **AJAX Powered:** Uses AJAX for smooth navigation and searching without full page reloads.
* **No jQuery Dependency:** Built with modern vanilla JavaScript for improved performance and reduced dependencies.

## Installation

### Via WordPress Admin Dashboard

1.  Navigate to **Plugins** > **Add New** in your WordPress admin dashboard.
2.  Search for "Public Folder Explorer".
3.  Click **Install Now** and then **Activate**.

### Manual Installation (via FTP)

1.  Download the latest version of the plugin from the [WordPress Plugin Repository](https://wordpress.org/plugins/public-folder-explorer/).
2.  Extract the downloaded ZIP file to your computer.
3.  Using an FTP client (e.g., FileZilla), upload the extracted plugin folder (`public-folder-explorer`) to the `/wp-content/plugins/` directory of your WordPress installation.
4.  Go to the **Plugins** page in your WordPress admin dashboard and activate the "Public Folder Explorer" plugin.

## Usage

1.  **Configure Settings:** After activating the plugin, go to **Settings** > **Folder Explorer** in your WordPress admin menu. Here you can configure:
    * **Base Directory:** The absolute path to the public folder on your server. **Ensure this path is correct and securely points to your intended public directory.**
    * **Allowed File Types:** A comma-separated list of file extensions that will be displayed and downloadable (e.g., `jpg,pdf,zip`).
    * **Hidden Folders:** A comma-separated list of folder names to hide from the frontend.
    * **Hidden Files:** A comma-separated list of file names to hide from the frontend.
    * **Frontend Title:** The title that will be displayed above the file explorer on the frontend.

2.  **Display on Frontend:** To display the file explorer on any WordPress page or post, simply use the `[public_folder_explorer]` shortcode within the content editor.

## Screenshots

* **Admin Settings Page:** (Add a screenshot of your plugin's settings page here)
* **Frontend File Explorer (List View):** (Add a screenshot of the frontend explorer in list view here)
* **Frontend File Explorer (Grid View):** (Add a screenshot of the frontend explorer in grid view here)
* **Frontend Search Results:** (Add a screenshot of the frontend search results here)

## Frequently Asked Questions (FAQ)

* **What is the "Base Directory"?**
    The base directory is the absolute server path to the main folder you want to make publicly accessible through the file explorer.
* **How do I find the absolute path to my folder?**
    Your hosting provider's documentation or support can usually help you find the absolute path to your server directories. You might also find tools within your hosting control panel (e.g., cPanel, Plesk) to determine this.
* **Why are some files or folders not showing up?**
    Check your "Allowed File Types," "Hidden Folders," and "Hidden Files" settings. Also, ensure that the files and folders exist in the specified "Base Directory" and that the server has the necessary permissions to read them.
* **Is this plugin secure?**
    The plugin includes measures to prevent directory traversal. However, it's crucial to configure the "Base Directory" correctly and ensure that only intended public files are placed within that directory. Regularly update the plugin to benefit from the latest security enhancements.
* **Can I customize the appearance?**
    The plugin provides basic styling. For more advanced customization, you can override the plugin's CSS in your theme's stylesheet.

## Support

For bug reports, feature requests, or general inquiries, please use the [support forums on WordPress.org](https://wordpress.org/support/plugin/public-folder-explorer/).

## Contributing

Contributions to the plugin are welcome! Please feel free to submit pull requests on GitHub.

## License

This plugin is released under the [GNU General Public License v2.0](https://www.gnu.org/licenses/gpl-2.0.html).

## Credits

Developed by [Your Name/Company Name] ([Your Website/Author URI]).

---

**Thank you for using Public Folder Explorer!**
