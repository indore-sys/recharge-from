// Google Apps Script for Client Requirement Form
// Copy this code to your Google Apps Script

function doPost(e) {
  try {
    // Check if event object exists
    if (!e || !e.postData) {
      return ContentService.createTextOutput(JSON.stringify({
        'result': 'error',
        'error': 'No data received'
      })).setMimeType(ContentService.MimeType.JSON);
    }

    // Log the request for debugging
    Logger.log("Request received");
    Logger.log("Request content: " + e.postData.contents);

    var spreadsheet = SpreadsheetApp.getActiveSpreadsheet();
    Logger.log("Spreadsheet URL: " + spreadsheet.getUrl());

    var sheet = spreadsheet.getActiveSheet();
    Logger.log("Sheet name: " + sheet.getName());

    // Parse the JSON data
    var data = JSON.parse(e.postData.contents);
    Logger.log("Data parsed successfully");

    // Define the correct header order (must match setupSheet)
    var headers = [
      'Timestamp',
      'projectName',
      'projectType',
      'projectDescription',
      'launchDate',
      'platform',
      'cmsPreference',
      'preferredCms',
      'shopifyEmail',
      'shopifyPassword',
      'hasDomain',
      'domainName',
      'domainProvider',
      'domainLogin',
      'domainUsername',
      'domainPassword',
      'preferredDomain',
      'hasHosting',
      'hostingProvider',
      'hostingPlan',
      'hostingLogin',
      'hostingUsername',
      'hostingPassword',
      'hostingPreference',
      'hasTheme',
      'themeName',
      'themeProvider',
      'themeLicense',
      'designStyle',
      'inspirationSites',
      'colorPreferences',
      'defaultPages',
      'additionalPageName_1',
      'additionalPageContent_1',
      'navigationStructure',
      'companyName',
      'tagline',
      'hasLogo',
      'logoLink',
      'brandGuidelines',
      'brandVoice',
      'contentReady',
      'homeContent',
      'homeContentFile',
      'aboutContent',
      'aboutContentFile',
      'servicesContent',
      'servicesContentFile',
      'imagesAvailable',
      'privacyPolicyContent',
      'privacyPolicyFile',
      'termsContent',
      'termsFile',
      'returnPolicy',
      'returnPolicyFile',
      'sellType',
      'productDriveLink',
      'productCategories',
      'productImagesLink',
      'filterAttributes',
      'customFeatures',
      'otherFeatures',
      'integrations',
      'formRequirements',
      'facebookUrl',
      'instagramUrl',
      'twitterUrl',
      'linkedinUrl',
      'youtubeUrl',
      'pinterestUrl',
      'tiktokUrl',
      'otherSocialMedia',
      'isEcommerce',
      'paymentMethods',
      'razorpayKeyId',
      'stripeKey',
      'paypalEmail',
      'otherPaymentMethod',
      'currencies',
      'otherCurrencyText',
      'taxRegion',
      'taxRate',
      'shippingType',
      'flatRate',
      'shippingZones',
      'shippingRates',
      'deliveryTimeframes',
      'inventoryType',
      'returnPolicy',
      'businessDescription',
      'keywords',
      'competitors',
      'hasAnalytics',
      'analyticsId',
      'gaEmail',
      'gaPassword',
      'hasGMB',
      'hasPrivacyPolicy',
      'privacyPolicyContent',
      'privacyPolicyLink',
      'hasTerms',
      'termsContent',
      'termsLink',
      'contactName',
      'contactEmail',
      'contactPhone',
      'businessEmail',
      'businessAddress',
      'additionalNotes'
    ];

    // Check if headers exist in sheet, if not create them
    var lastColumn = sheet.getLastColumn();
    if (lastColumn === 0) {
      sheet.getRange(1, 1, 1, headers.length).setValues([headers]);

      // Format header row
      var headerRange = sheet.getRange(1, 1, 1, headers.length);
      headerRange.setFontWeight('bold');
      headerRange.setBackground('#4285F4');
      headerRange.setFontColor('white');
      headerRange.setFontSize(11);
      headerRange.setHorizontalAlignment('center');
      headerRange.setVerticalAlignment('middle');

      Logger.log("Headers created and formatted");
    }

    // Create a row array with values in the same order as headers
    var row = headers.map(function(header) {
      var value = data[header];

      // Handle file data - store only filename, not base64 data (too large for cells)
      if (header.includes('File') && header.includes('_data')) {
        return '[File data - too large for cell]';
      }

      if (Array.isArray(value)) {
        return value.join(', ');
      }
      return value || '';
    });

    // Replace first element (Timestamp) with actual timestamp
    row[0] = new Date();

    // Append the row to the sheet
    sheet.appendRow(row);
    Logger.log("Row appended successfully");

    // Auto-resize columns to fit content
    sheet.autoResizeColumns(1, headers.length);

    // Set text wrap for all columns with long content
    sheet.getRange(1, 1, sheet.getLastRow(), headers.length).setWrap(true);

    // Set row height for better readability
    sheet.setRowHeight(1, 30); // Header row height
    for (var i = 2; i <= sheet.getLastRow(); i++) {
      sheet.setRowHeight(i, 60); // Data row height
    }

    // Freeze header row
    sheet.setFrozenRows(1);

    return ContentService.createTextOutput(JSON.stringify({
      'result': 'success',
      'row': row.length
    })).setMimeType(ContentService.MimeType.JSON);

  } catch(error) {
    Logger.log("Error: " + error.toString());
    return ContentService.createTextOutput(JSON.stringify({
      'result': 'error',
      'error': error.toString()
    })).setMimeType(ContentService.MimeType.JSON);
  }
}

// Optional: Function to create the sheet with headers
function setupSheet() {
  try {
    var spreadsheet = SpreadsheetApp.getActiveSpreadsheet();
    Logger.log("Spreadsheet URL: " + spreadsheet.getUrl());

    var sheet = spreadsheet.getActiveSheet();
    Logger.log("Sheet name: " + sheet.getName());

    sheet.clear();
    Logger.log("Sheet cleared");

    // Set headers (add all form field names here)
    var headers = [
      'Timestamp',
      'projectName',
      'projectType',
      'projectDescription',
      'launchDate',
      'platform',
      'cmsPreference',
      'preferredCms',
      'shopifyEmail',
      'shopifyPassword',
      'hasDomain',
      'domainName',
      'domainProvider',
      'domainLogin',
      'domainUsername',
      'domainPassword',
      'preferredDomain',
      'hasHosting',
      'hostingProvider',
      'hostingPlan',
      'hostingLogin',
      'hostingUsername',
      'hostingPassword',
      'hostingPreference',
      'hasTheme',
      'themeName',
      'themeProvider',
      'themeLicense',
      'designStyle',
      'inspirationSites',
      'colorPreferences',
      'defaultPages',
      'additionalPageName_1',
      'additionalPageContent_1',
      'navigationStructure',
      'companyName',
      'tagline',
      'hasLogo',
      'logoLink',
      'brandGuidelines',
      'brandVoice',
      'contentReady',
      'homeContent',
      'homeContentFile',
      'aboutContent',
      'aboutContentFile',
      'servicesContent',
      'servicesContentFile',
      'imagesAvailable',
      'privacyPolicyContent',
      'privacyPolicyFile',
      'termsContent',
      'termsFile',
      'returnPolicy',
      'returnPolicyFile',
      'sellType',
      'productDriveLink',
      'productCategories',
      'productImagesLink',
      'filterAttributes',
      'customFeatures',
      'otherFeatures',
      'integrations',
      'formRequirements',
      'facebookUrl',
      'instagramUrl',
      'twitterUrl',
      'linkedinUrl',
      'youtubeUrl',
      'pinterestUrl',
      'tiktokUrl',
      'otherSocialMedia',
      'isEcommerce',
      'paymentMethods',
      'razorpayKeyId',
      'stripeKey',
      'paypalEmail',
      'otherPaymentMethod',
      'currencies',
      'otherCurrencyText',
      'taxRegion',
      'taxRate',
      'shippingType',
      'flatRate',
      'shippingZones',
      'shippingRates',
      'deliveryTimeframes',
      'inventoryType',
      'returnPolicy',
      'businessDescription',
      'keywords',
      'competitors',
      'hasAnalytics',
      'analyticsId',
      'gaEmail',
      'gaPassword',
      'hasGMB',
      'hasPrivacyPolicy',
      'privacyPolicyContent',
      'privacyPolicyLink',
      'hasTerms',
      'termsContent',
      'termsLink',
      'contactName',
      'contactEmail',
      'contactPhone',
      'businessEmail',
      'businessAddress',
      'additionalNotes'
    ];

    sheet.getRange(1, 1, 1, headers.length).setValues([headers]);
    Logger.log("Headers set");

    // Format header row
    sheet.getRange(1, 1, 1, headers.length).setFontWeight('bold');
    sheet.getRange(1, 1, 1, headers.length).setBackground('#4285F4');
    sheet.getRange(1, 1, 1, headers.length).setFontColor('white');
    Logger.log("Headers formatted");

    // Auto-resize columns
    sheet.autoResizeColumns(1, headers.length);
    Logger.log("Columns resized");

    Logger.log("Setup completed successfully");
  } catch(error) {
    Logger.log("Error in setupSheet: " + error.toString());
  }
}

// Test function to check spreadsheet connection
function testConnection() {
  var spreadsheet = SpreadsheetApp.getActiveSpreadsheet();
  var url = spreadsheet.getUrl();
  Browser.msgBox("Spreadsheet URL:\n\n" + url);
  Logger.log("Spreadsheet URL: " + url);
}
