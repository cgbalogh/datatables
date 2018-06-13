.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _technical-background:

Technical Background
====================

This section is intended to give you a short overview what actions take place in the background.

* AJAX calls in the background
* dataTables invocation
* Selecting repositories and properties/attributes
* Executing the queries
* Language settings

AJAX calls in the background
----------------------------
The AJAX calls in the background are managed by jQuery. The call itself is embedded in the dt-helper.js file in the Resources/Public/Scripts folder. The paramters for the call are set via TypoScript by including the static template. If you want to change these settings you can do this via TypoScript in any template that will be "seen" by the plugin. Please refer to setup.txt in Configuration/TypoScript.

The ID can be set in the constant editor as well. It defaults to a value of 1200.

dataTables invocation
---------------------
The jQuery plugin dataTables plugin will be called from the script datatables/Resources/Public/Scripts/dt-helper.js. At the moment the configuration is adjusted to the needs within this extension. Feel free to change the parameters for the dataTables call but keep in mind that this might confligt with future releases of the extension. Your suggestions are welcome.

At the moment the paramters are set as follows:

* Language settings will be feched automatically from a json file located in datatables/Resources/Public/Scripts/datatbles.<lang_id>.json.
* bProcessing is set to **true**.
* ajax will be automatically set.
* serverSide is set to **true**.
* responsive is set to **true**; the required datatbales extension is loaded automatically.
* stateSave is set to **true**.
* The pagelength menu is set to the values 10, 20, 50, 100 and 200.

Selecting repositories and properties/attributes
------------------------------------------------
The repositories will be extracted from the autoloaded extensions. By choosing a desired repository the flexfoem tries to build a mock object to be able to access the child classes. As long as your extension follows the MVC-conventions this will take place automatically. If you modify your domain model and/or repository classes make sure, that the properties have a proper @var annotaion set. In your repository there will be expected a method **countAll**, which exists if you subclass the Extbase/Persistence/Repository as usual.

On some occasions the object constructors deliver warnings (yellow) or errors (red) during the flexform configuration process. In that cases you will probably *not* be able to use the extension without further adjustments.

Executing the queries
---------------------
The queries will be processed in three major steps:

1. Render the empty table with all headers computed first.
2. Activate DataTable.
3. Fetch data from server via AJAX and display

Render the empty table
^^^^^^^^^^^^^^^^^^^^^^

This process is fairliy easy since the plugin just delivers an ordinary FLUID-based template. The number of columns will be set automatically. The headers will be taken from the language files if available. If the language file is not found or the required entry is missing, the header path will be displayed as header. In this case you might add your localized header by using the extension Translate available in TER.

Activate DataTable
^^^^^^^^^^^^^^^^^^

Normaly you won't notice this step. There might be cases where activation fails. In most cases this is due to multiple installations of jQuery and/or datatables. To debug a problem it is best to activate your browser's JS console and see what's wrong.

Fetch data from Server via AJAX and Display
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

This is accomplished by calling the exact same controller action (list) in  DatatbleController.php. THe onmly differenc is that if the action sees the pageType parameter, the delivery switches to the plain JSON data array instead of the filled list template generated in step 1. If this fails you'll probably get a JSON error because instead of a valid JSON string you get an error message thrown by Typo3 which cannot be interpreted by datatable. If this occurs you should check the selected attributes first. In some rare cases the query is not able to produce a vlid result.

The query itself is does not rely on the repository but is freshly constructed. The necessary data will be fetched from the flexform settings. That's why it ius necessary to include the content uid and the page uid in the queries. Otherwise it would not be possible to fetch the required configuration data from the content element. Usually AJAX calls will hit page with uid = 0. And this technique makes it possible two have two or more tables on a single page.

Language Settings
-----------------

The language settings will be derived automatically from the lang="" attribute in the html tag. Of course you can modify the language settings to your own needs. There are two resources for the language settings:

* The language configuration of datatables in Resources/Publilc/Scripts/datatables.<lang_id>.json
* The language settings in Resources/Private/Language. If you want to use the extension Translate from TER to add additional languages it is recommended to save the translated files with the extension.

The basic TypoScript settings suppose that you are using English as default language with sys_language_uid set to 0. If you want to adjust the settings to support a different language (e.g. if the whole project is not localized), you might want to change the language configuration for the AJAX calls. Thts is necessary because the rendering of DateTime objects will be generated by using format strings in locallang.xlf.

Add a block like this to the setup code of your root template (example is given for German):

.. code-block:: TypoScript

	DATATABLES_Plugintyp.config {
		language = de
		locale_all = de_AT.utf-8
		metaCharset = utf-8
		sys_language_uid = 1
	}

Normal Text?

:ref:`../UsersManual/General`
