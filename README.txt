INTRODUCTION
------------
Masquerade Toolbar module works with the Masquerade module and provides a
modern floating toolbar interface for quick user switching.

This module is inspired by and based on the Masquerade Float Block module
but features a completely redesigned architecture and improved
UI/UX for better usability and user experience.

Key improvements over the original float block include:
 * Modern, collapsible toolbar design
 * Recent users quick-access list
 * Keyboard shortcuts support
 * Mobile-responsive design with optional hiding on small screens
 * Display of current and original user information
 * One-click switch back to original user
 * Service-based architecture for better maintainability
 * Event-driven system for tracking masquerade sessions
 * Enhanced caching and performance

The toolbar appears as a floating widget that can be minimized when not in use,
providing quick access to user switching without cluttering the interface.

REQUIREMENTS
------------
This module requires the following module:
 * Masquerade (https://www.drupal.org/project/masquerade)

INSTALLATION
------------
Install as you would normally install a contributed Drupal module. Visit:
https://www.drupal.org/docs/extending-drupal/installing-modules
for further information.

CONFIGURATION
-------------
Once the module is enabled, users with "Use Masquerade Toolbar" permission
will see the floating toolbar on all pages.

Configuration options can be found at:
  Administration > Configuration > People > Masquerade Toolbar Settings
  (admin/config/people/masquerade-toolbar)

Available configuration options:

 * Position - Choose where the toolbar appears on the page:
   - bottom-right (default)
   - bottom-left
   - top-right
   - top-left

 * Collapsed by default - Start the toolbar in minimized state

 * Show recent users - Display a list of recently masqueraded users for
   quick access

 * Show user roles - Display role information for users in the interface

 * Enable keyboard shortcuts - Allow keyboard navigation and shortcuts

 * Hide on mobile - Automatically hide the toolbar on mobile devices

USAGE
-----
Once configured, the toolbar will appear as a floating widget on the page.

 * Click the toolbar icon to expand/collapse the interface
 * Use the autocomplete search field to find users by name or email
 * Click on any recent user to quickly switch to that user
 * When masquerading, you'll see both current and original user information
 * Click "Switch Back" to return to your original account
 * Drag the toolbar to reposition it (if your theme supports it)

PERMISSIONS
-----------
The module provides the following permission:

 * Use Masquerade Toolbar - Allows users to see and use the toolbar
   (Note: Users must also have Masquerade permissions to switch users)

ARCHITECTURE NOTES
------------------
This module uses a service-based architecture with the following components:

 * MasqueradeToolbarManager service - Handles all toolbar logic
 * AutocompleteController - Provides user search functionality
 * MasqueradeEventSubscriber - Tracks masquerade sessions and recent users
 * ToolbarAccessCheck - Controls access to toolbar features
 * SettingsForm - Provides configuration interface

The module attaches to pages via hook_page_bottom() and hook_page_attachments()
for optimal performance and caching.

COMPARISON WITH MASQUERADE FLOAT BLOCK
---------------------------------------
While inspired by Masquerade Float Block, this module offers:

 * Service-based architecture instead of procedural code
 * Enhanced UI with autocomplete search
 * Recent users tracking
 * More configuration options
 * Better mobile support
 * Improved accessibility
 * Event-driven architecture for extensibility

MAINTAINERS
-----------
This module was developed as an enhanced alternative to Masquerade Float Block
for modern Drupal versions.
