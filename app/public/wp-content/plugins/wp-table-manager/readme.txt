=== WP Table Manager ===
Contributors: Joomunited
Donate link: 
Tags: table manager, nice table
Requires at least: 3.5
Tested up to: 6.3.2
Stable tag: 3.9.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

WP Table Manager is a table manager that helps you to categorize, create & edit table easily. UX is AJAX powered, the easiest table manager ever created.


== Description ==

More informations available here: http://www.joomunited.com/wordpress-products/wp-table-manager

Main plugins from JoomUnited:
WP Media Folder: https://www.joomunited.com/wordpress-products/wp-media-folder
WP File Download: https://www.joomunited.com/wordpress-products/wp-table-manager
WP Table Manager: https://www.joomunited.com/wordpress-products/wp-file-download
WP Meta SEO: https://www.joomunited.com/wordpress-products/wp-meta-seo
WP Latest Posts: https://www.joomunited.com/wordpress-products/wp-latest-posts

WP Table Manager is the only table manager for WordPress that offers a full spreadsheet interface to manage tables. 
Create a table, apply some really cool themes and start edit tables data. The tool is perfect for webmaster and final client.

As a webmaster you'll like to use advanced tools like html cell edition, table copy, use some calculation, edit custom CSS, theme modification, excel import/export and so on. 
Then, editing a table becomes as simple as click on cell, edit data with or without visual text editor and it's automatically saved! 
Usually tables require HTML/CSS knowledge, this is no longer the case, this extension is really easy for beginners. 
It works in the same way both public and admin side, plus, full table edition can be done right from your editor in a lightbox.

Main advantages:
- Manage tables like in a spreadsheet
- 6 themes included
- Visual & HTML cell edition
- AJAX automatic saving and undo
- Sortable data on frontend
- Create chart from data
- Resize line and column using handles
- Copy cell with drag'n drop
- Copy the full table in one click
- Responsive or scroll mode
- Excel import/export
- Get WP Table manager, the only and most advanced table manager for WordPress 


== Installation ==

1. Upload `WP Table Manager` to the `/wp-content/plugins/` directory or browse and upload zip file from WordPress standard installer
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Use the "Table Manager" menu to edit table or edit it from your editor using a dedicated button

== Frequently asked questions ==

= How do I add tables in my WordPress content? =

Just open you content and you'll a button named "WP Table Manager" at the top left of your editor.

= Can I put tables into categories? =

Yes you can classified tables into categories. 

== Changelog ==

= 3.9.4 =
 * Add : Advanced filtering: Possibility to setup filter type for each column in the table
 * Fix : Sort function improvement

= 3.9.3 =
 * Fix : Conflict with Hello Elementor theme
 * Fix : Missing last row when fetch from Google Sheet in some cases

= 3.9.2 =
 * Fix : Fatal error in PHP 7.4

= 3.9.1 =
 * Add : Option to open hyperlinks in tables as new tab
 * Fix : Editing lock cells in a database table using the command edit line

= 3.9.0 =
 * Add : New options for chart creation: select custom X-Y axis
 * Add : Chart edition: custom labels and legend
 * Add : Customize chart data scale
 * Add : Possibility to select 2 columns separated with control key to create chart
 * Add : List charts below table in the table list screen
 * Add : Improved filter in repeatedHeader mode on mobile
 * Fix : Cell border when fixed column on firefox

= 3.8.1 =
 * Fix : Missing php class
 * Fix : Error message when read excel file

= 3.8.0 =
 * Add : Preview table in Gutenberg block
 * Add : Preview chart in Gutenberg block
 * Add : Improve compute recursively in some formula on table
 * Add : Possibility to keep formula in cell as text (not calculating formula result) when import/sync Excel file
 * Fix : Improve the function import excel file

= 3.7.0 =
 * Add : Possibility to zoom in/out table content at the table editing screen
 * Add : Option to Print table on front-end
 * Add : Strikethrough button for cell content edit
 * Fix : Import Excel function

= 3.6.1 =
 * Fix : Display table issue in repeatedHeader mode
 * Fix : Display table issue in hiding columns mode

= 3.6.0 =
 * Add : Possibility to import data from CSV file to table
 * Fix : Improved performance for import Excel function
 * Fix : Error when running cronjob syncing Excel file with table
 * Fix : Conflict pagination width fixed columns

= 3.5.6 =
 * Fix : Saving table fail in some case after editing cell
 * Fix : Custom css in editing table page

= 3.5.5 =
 * Fix : Table style on front-end
 * Fix : PHP 8.1 warning in JU framework

= 3.5.4 =
 * Fix : Google sync not working

= 3.5.3 =
 * Fix : Prevent adding XSS code into the table cell
 * Fix : Implement nonce check to avoid vulnerable issue delete token

= 3.5.2 =
 * Fix : Error at table editing screen when using Safari browser
 * Fix : Table pagination error on the front-end

= 3.5.1 =
 * Add : Compatibility with PHP 8.1
 * Add : Possibility to select a specific sheet in Excel file for import and synchronization
 * Fix : Chart issue when using Divi builder on front-end

= 3.5.0 =
 * Add : Possibility to insert a cell selection of table into the content
 * Add : Possibility to add Google fonts from the configuration
 * Add : Possibility to download and load Google fonts locally (GDPR)

= 3.4.1 =
 * Fix : Wrong HTML cell type after removing rows
 * Fix : Update JU translation tool

= 3.4.0 =
 * Add : 3 new table themes
 * Add : Possibility to copy cells with inline HTML
 * Add : Possibility to copy cells with styling

= 3.3.1 =
 * Fix : Update style in new themes

= 3.3.0 =
 * Add : New theme design
 * Add : Wrapping Text in Coloumn
 * Fix : Format date to cells
 * Fix : fix divi warning on PHP 8
 * Fix : fix merge rows when has sort and not paging
 * Fix : fix export table created from data from OneDrive

= 3.2.1 =
 * Fix : Hidden column error on front-end
 * Fix : Conflict Elementor widget
 * Fix : DateTime cell error in database table

= 3.2.0 =
 * Add : Possibility to add more fonts for table content
 * Add : Access limitation: Limit the table edition by column, line or dataset by user or user role.
 * Add : Lock columns in database table edition
 * Add : Option to download database table on front-end
 * Fix : Show border cells on front-end when merge cells
 * Fix : Some sites do not load the php-sql-parser library
 * Fix : Show hyperlink on front-end when enable table pagination

= 3.1.1 =
 * Fix : Display database table issue on front-end
 * Fix : Conflict border top with Divi page builder
 * Fix : Missing first row on front-end when enable pagination

= 3.1.0 =
 * Add : Additional format for cells (support symbols)
 * Add : Undo/redo buttons
 * Add : Excel importer improvment
 * Add : Better DIVI and Avada support
 * Add : Option to define border width for cells
 * Add : Possibility to remove alternate colors in a table
 * Add : Custom CSS editor design refresh
 * Fix : Alternate colors display on front-end
 * Fix : Sort icon on front-end
 * Fix : Default rows height

= 3.0.4 =
 * Fix : Display issue in repeated header responsive
 * Fix : Database-table doesn't open in edit table page

= 3.0.3 =
 * Fix : WP Table Manager user role conflict WP Media Folder

= 3.0.2 =
 * Fix : Avada JS error on table loading

= 3.0.1 =
 * Fix : Chart display error on front-end

= 3.0.0 =
 * Add : Possibility to do database custom queries
 * Add : Editing cell content in database tables
 * Add : Hide column in front-end in tables from database

= 2.9.1 =
 * Fix : Missing category owner and table owner setting

= 2.9.0 =
 * Add : Office 365 Excel files synchronization (OneDrive)
 * Add : Office 365 Excel files for business synchronization
 * Add :  Fetch only data and style from Office 365 Excel files
 * Add :  Automatic synchronization with Office 365 Excel files

= 2.8.6 =
 * Add : WP Table Manager table module for Avada page builder
 * Add : WP Table Manager chart module for Avada page builder

= 2.8.5 =
 * Fix : WP Table Manager divi widget conflict WP File Download

= 2.8.4 =
 * Add : WP Table Manager table module for WPBakery page builder
 * Add : WP Table Manager chart module for WPBakery page builder

= 2.8.3 =
 * Fix : Responsive mode: Repeated header
 * Fix : WP Table Manager table module for Divi when using only the Divi builder plugin
 * Fix : Wrong column width when sync with Excel file
 * Fix : Update ChartJS library

= 2.8.2 =
 * Add : WP Table Manager table module for Divi page builder
 * Add : WP Table Manager chart module for Divi page builder

= 2.8.1 =
 * Fix : Conflict with Polylang plugin
 * Fix : Import excel button doesn't work on WordPress 5.6
 * Fix : Change chart type doesn't work in some cases

= 2.8.0 =
 * Add : New responsive mode: Repeated header
 * Add : Implement cronjob Excel/Google Sheet synchronization
 * Add : Implement Google Sheet push notification
 * Add : Support 3rd party plugin shortcode in table cells

= 2.7.5 =
 * Fix : Can't update some cells when deleting column
 * Fix : Error in front-end when the inserted table was deleted

= 2.7.4 =
 * Add : WP Table Manager table widget for Elementor page builder
 * Add : WP Table Manager chart widget for Elementor page builder
 * Fix : Some errors on table editor screen with Safari browser

= 2.7.3 =
 * Fix : Currency symbol in calculation functions
 * Fix : Copy /paste function
 * Fix : Drag cells to copy
 * Fix : Do not display filters when data are grouped on responsive mode

= 2.7.2 =
 * Fix : Table copy is not executed
 * Fix : Add a space as thousand separator in number format
 * Fix : Synchronize Excel function
 * Fix : Browse server window for Excel file style
 * Fix : Undo/redo right click function

= 2.7.1 =
 * Fix : Set table height default to auto
 * Fix : Check url when fetch file from Google Spreadsheets
 * Fix : Table style on front-end
 * Fix : Warning when upgrading from old version

= 2.7.0 =
 * Add : New UX design of the table editor
 * Add : New UX design of the table and category manager
 * Add : Better responsive mode with cell group and accordion
 * Add : Possibility to define data type for column
 * Add : Improve table auto saving when table not edited
 * Add : Google Sheets import detects when the data are not accessible
 * Add : Improve loading table performance on front-end
 * Add : New database structure to store and modify large tables
 * Add : New design for table from database creation
 * Add : PHP 7.4 compatibility
 * Add : Option to remove all tables data when uninstalling the plugin

= 2.6.9 =
 * Fix : JoomUnited Updater compatible with WordPress 5.5

= 2.6.8 =
 * Fix : Translation sharing issue in some browsers

= 2.6.7 =
 * Add : Implement the block preview
 * Fix : change position of tooltip
 * Fix : Apply the decimal separator and the thousand separator separator setting when import excel file

= 2.6.6 =
 * Add : Sync Google Sheets automatically on frontend page load
 * Fix : Apply dateFormat setting into table sort function
 * Fix : Remove unused taxonomy wptm-tag

= 2.6.5 =
 * Fix : Fix Jutranslation url

= 2.6.4 =
 * Fix : High commas as thousand separator option doesn't apply
 * Fix : Table not displayed in ACF field

= 2.6.3 =
 * Add : add wptm gutenberg block

= 2.6.2 =
 * Fix : warning on php 7.2

= 2.6.1 =
 * Fix : The function convert background color of default table to alternate color
 * Fix : The Functionality checks null when importing file .xlsx

= 2.6.0 =
 * Add : Rebuild the alternate color tool: create and apply your 2 lines colors
 * Add : Frontend button to download the table as XLSX file
 * Add : Upgrade phpspreadsheet library.
 * Fix : Improve function Import and Export files xlsx

= 2.5.3 =
 * Fix : conflict anchor-header plugin
 * Fix : site using http and https parallel

= 2.5.2 =
 * Add : Spreadsheet style fetch function autosaved
 * Add : Get hyperlink from Google Spreadsheets
 * Add : Get lines and columns sizes from Google Spreadsheets
 * Fix : Error page loading in Microsoft Edge
 * Fix : Merge cells properly when auto sync is activated
 * Fix : Change the color pickup by cell function

= 2.5.1 =
 * Fix : Chat color selector
 * Fix : Right click menu is returning wrong values
 * Fix : Improve design in editor tooltip table manager

= 2.5.0 =
 * Add : Admin full UX redesign
 * Add : Allow to edit data range of a chart
 * Add : Improve display ability in large/big table

= 2.4.3 =
 * Fix : Sharing translations

= 2.4.2 =
 * Fix : Improved the ability to create tables from database
 * Fix : Improved the ability to create tables from spreadsheet link

= 2.4.1 =
 * Fix : Enhance code readability and performance

= 2.4.0 =
 * Add : Calculation functions: DATE, DAY, DAYS, DAYS360, OR, XOR, AND
 * Add : Possibility to make calculation on money cells
 * Add : Addition of date calculation functions

= 2.3.2 =
 * Fix : Missing access permissions on new install

= 2.3.1 =
 * Fix : Update the updater :) for table manager 2.3.0
 * Fix : Multisite installation plugin deployment

= 2.3.0 =
 * Add : Setup rights per user role on table categories: Create, Delete, Edit, Edit own
 * Add : Setup rights per user role on tables: Create, Delete, Edit, Edit own
 * Add : Setup rights per user role to access to WP Table Manager UX
 * Add : Add a font color for cell highlight feature
 * Add : Possibility to sort a column by default, on page load

= 2.2.10 =
 * Fix : Display error when enable Freeze first
 * Fix : Pagination not displaying in table category

= 2.2.9 =
 * Fix : Import excel file which contain non utf-8 characters

= 2.2.8 =
 * Fix : Error when JU framework is not installed

= 2.2.7 =
 * Fix : Issue on upgrading from light version to full version

= 2.2.6 =
 * Fix : Update the updater for WordPress 4.8

= 2.2.5 =
 * Fix : Use default en_US language

= 2.2.4 =
 * Fix : Text domain related problem for JUTranslation

= 2.2.3 =
 * Add : JUTranslation implementation

= 2.2.2 =
 * Fix : CSS style frontend rendering
 * Fix : Import XLS tables style not complete

= 2.2.1 =
 * Fix : Excel importer issue
 * Fix : Display issue when freezing row on mobile devices

= 2.2.0 =
 * Add : Use WP Table manager with page builder: ACF
 * Add : Use WP Table manager with page builder: Beaver Builder
 * Add : Use WP Table manager with page builder: DIVI Builder
 * Add : Use WP Table manager with page builder: Site Origine
 * Add : Use WP Table manager with page builder: Themify builder
 * Add : Use WP Table manager with page builder: Live composer
 * Fix : Pagination display on database tables

= 2.1.0 =
 * Add : Create tables from WordPress database (not only WordPres tables, all the tables from the database)
 * Add : Automatic styling and filtering for database tables
 * Add : Table automatic update on database incrementation
 * Add : Database source: table, column, filters, define ordering and column custom name
 * Add : Create chart from database table
 * Fix : First time the tooltip is displayed it blinks (JS fix)

= 2.0.1 =
 * Fix : Admin column header not responsive layout
 * Fix : PHP7 compatibility for Excel table export

= 2.0.0 =
 * Add : Enhanced .xls Import/Export: possibility to Import/Export only data
 * Add : Handle Excel styles on Import/Export: HTML link, font color, font size, cell background color, cell border
 * Add : Sheet data synchronization: Select an excel file on the server, fetch data and define a sync delay
 * Add : Sheet data synchronization: Select a Google Sheet, fetch data and define a sync delay
 * Add : Notification when a file has an external sync to avoid data lost
 * Fix : Vertical scrolling issue on large table

= 1.4.1 =
 * Fix : PHP7 JU framework compatibility

= 1.4.0 =
 * Add : Data filtering and ordering tool as an option
 * Fix : Language update

= 1.3.0 =
 * Add : Add column and line freezing
 * Fix : Issue loading twice WPTM in a post
 * Fix : Height available of screen viewport

= 1.2.1 =
 * Fix : Tooltip have wrong size when column is resized

= 1.2.0 =
 * Add : Generate tooltip on cell, activate through a global option
 * Add : Respect WordPress user roles to give access to table data
 * Fix : Menu name change to fit WordPress admin column width
 * Fix : Polylang JS conflict
 * Fix : Custom CSS edition cursor not visible

= 1.1.1 =
 * Fix : Cell mergin not reflecting on public side
 * Fix : Table copy not copy all data

= 1.1.0 =
 * Add : JoomUnited automatic updater
 * Add : Performance optimization on loading time and for big tables
 * Add : Possibility to move a table from one category to another
 * Add : Possibility to reorder tables in a category
 * Add : Shortcode per table and chart
 * Add : Add codemiror in custom CSS edition window

= 1.0.0 =
 * Add : Initial release



== Upgrade notice ==

use our automatic updater or uninstall/install new version.

== Arbitrary section 1 ==