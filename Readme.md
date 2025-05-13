# WooCommerce Auto Stock Distributor by Weight

A lightweight WooCommerce plugin that automatically distributes stock quantity to product variations based on their weight in grams. The admin sets a total quantity in pounds (lbs), and the plugin auto-calculates and assigns stock to each product variation accordingly.

## How It Works

- Admin enters total quantity in pounds (lbs) for the main variable product.
- Plugin converts pounds to grams (1 lb = 450 grams).
- Each product variation's weight (e.g., 3.5g, 6g) is parsed dynamically.
- Stock for each variation is calculated by dividing total grams by the variation's weight.
- Stock values are automatically updated and managed within WooCommerce.

## Features

- Simple, clean integration with WooCommerce.
- Dynamic parsing of variation weights.
- Automated stock management based on weight calculations.
- No external plugins required.
- Secure and sanitizes inputs.

## Installation

1. Download the plugin file and place it in `wp-content/plugins/wc-auto-stock-weight/`.
2. Activate the plugin from your WordPress admin panel.

## Usage

1. Edit a WooCommerce variable product.
2. Enter the total quantity in pounds into the custom field "Main Stock (lbs)".
3. Ensure variation weights are specified correctly (e.g., "3.5g", "6g").
4. Save/update the product to automatically distribute the stock.

## Requirements

- WooCommerce installed and activated.
- WordPress 5.0 or later.

## License

This project is open source and available under the MIT License.

## Author

- Developed by Haris Maqsood

## Support

If you encounter any issues or have suggestions, feel free to open an issue on GitHub.
