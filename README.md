# OL Shortcodes (Search, Account, Add Listing)

Custom shortcodes for **OneListing + Directorist**, designed to be easy to drop into headers/footers and **Elementor**.

## Features
- **[ol_search]** → search form or trigger icon (matches OneListing header icon style)
- **[ol_account]** → account dropdown using **Directorist** avatar (`pro_pic`), shows **Dashboard** & **Logout**
- **[ol_add_listing]** → “Add Listing” button, auto-links to Directorist Add Listing page
- **Security** → XML-RPC toggle in **Settings → OL Shortcodes**

## Requirements & Compatibility
- **WordPress:** Requires 5.8+, **Tested up to 6.8.2**
- **PHP:** Requires 7.4+, **Tested up to 8.3.24**
- **Directorist (Business Directory):** **Required**, **Tested up to 8.4.5**
- **OneListing Theme:** **Required**, **Tested up to 2.0.12 (Pro)**
- **Recommended Theme:** **OneListing Pro Child**
- **Elementor:** **Tested with 3.31.2**
- **Elementor Pro:** **Tested with 3.31.2**

> Elementor is **not required**, but fully supported. Use Elementor’s **Shortcode** widget to place these anywhere.

## Installation
1. Upload the plugin to `/wp-content/plugins/ol-shortcodes/`.
2. Activate it from **Plugins**.
3. (Optional) Go to **Settings → OL Shortcodes** to disable XML-RPC.

## Shortcode Examples
- Search trigger icon:  
  `[ol_search mode="trigger"]`
- Account dropdown (Directorist avatar):  
  `[ol_account show_name="false" modal="true"]`
- Add Listing button:  
  `[ol_add_listing text="Add Listing"]`

## Notes
- Avatars are pulled from Directorist user meta `pro_pic` (falls back to Directorist helper / Gravatar).
- Dashboard/Logout links use Directorist permalinks when available.

## License
GPLv2 or later.
