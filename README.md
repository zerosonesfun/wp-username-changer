# WP Username Changer

**Let users change their username.**

WP Username Changer is a WordPress plugin that allows users to change their usernames through a shortcode-enabled page. It also includes a system for managing username change requests after the user has reached their limit.

## How It Works

1. **User-Initiated Changes**:
   - Add the provided shortcode `[username_changer]` to a page.
   - Users can visit this page and change their username **up to 2 times**.

2. **Request System**:
   - After reaching the 2-change limit, users can still submit requests for further username changes via the same shortcode-enabled page.
   - These requests are sent to the admin for review.

3. **Admin Management**:
   - Admins can review all submitted username change requests in the WordPress admin panel by navigating to **Users > Username Change Requests**.
   - Requests can be approved or dismissed directly from the admin panel.

## Features

- **Shortcode-Based**: Easily add the functionality to any page using the `[username_changer]` shortcode.
- **Change Limits**: Users can change their usernames up to 2 times.
- **Request Handling**: A seamless request system for additional changes beyond the limit.
- **Admin Control**: Approve or dismiss requests directly from the WordPress admin dashboard.

## Installation

1. Download the plugin files or clone the repository:
   ```bash
   git clone https://github.com/zerosonesfun/wp-username-changer.git
   ```
2. Upload the `wp-username-changer` folder to the `/wp-content/plugins/` directory.
3. Activate the plugin through the 'Plugins' menu in WordPress.

## Usage

1. **Add the Shortcode**:
   - Create or edit a page in WordPress.
   - Add the `[username_changer]` shortcode to the page content.
   - Publish or update the page.

2. **Admin Workflow**:
   - To review and manage username change requests, navigate to **Users > Username Change Requests** in the WordPress admin dashboard.

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher

## Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository.
2. Create a new branch for your feature or bug fix:
   ```bash
   git checkout -b feature-name
   ```
3. Commit your changes:
   ```bash
   git commit -m "Add feature or fix bug"
   ```
4. Push the branch:
   ```bash
   git push origin feature-name
   ```
5. Open a pull request.

## License

This project is licensed under the GPL 3 License.

## Support

If you encounter any issues or have questions, feel free to [open an issue](https://github.com/zerosonesfun/wp-username-changer/issues).

---

Let me know if there’s anything else you'd like to adjust!
