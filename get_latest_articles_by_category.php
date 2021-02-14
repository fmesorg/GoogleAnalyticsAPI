<?php

// Load the Google API PHP Client Library.
require_once __DIR__ . '/googleAPI/vendor/autoload.php';

/*
    "Research Ethics",
    "Clinical Ethics",
    "Public Health Ethics",
    "Reproductive Ethics",
    "Healthcare Humanities",
    "Humanitarian Ethics",
    "Organisational Ethics",
    "Students Corner",
*/

$analytics = initializeAnalytics();
$categories = [
    "COVID-19",
    "Articles",
    "Comments",
    "Editorials",
    "From the Press",
    "Case Studies",
];
//This list should match what is in the mostread-bycategory.php file

$result=array();

foreach ($categories as $category ){
    $response = getReport($analytics,$category);
    $result[$category]=printResults($response);
}

echo json_encode($result);


/**
 * Initializes an Analytics Reporting API V4 service object.
 *
 * @return An authorized Analytics Reporting API V4 service object.
 */
function initializeAnalytics()
{

    // Use the developers console and download your service account
    // credentials in JSON format. Place them in this directory or
    // change the key file location if necessary.
    $KEY_FILE_LOCATION = __DIR__ . '/service-account-credentials.json';

    // Create and configure a new client object.
    $client = new Google_Client();
    $client->setApplicationName("Hello Analytics Reporting");
    $client->setAuthConfig($KEY_FILE_LOCATION);
    $client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
    $analytics = new Google_Service_AnalyticsReporting($client);

    return $analytics;
}


/**
 * Queries the Analytics Reporting API V4.
 *
 * @param service An authorized Analytics Reporting API V4 service object.
 * @return The Analytics Reporting API V4 response.
 */
function getReport($analytics,$category) {

    // Replace with your view ID, for example XXXX.
    $VIEW_ID = "74396426";

    // Create the DateRange object.
    $dateRange = new Google_Service_AnalyticsReporting_DateRange();
    $dateRange->setStartDate("2016-01-01");
    $dateRange->setEndDate("yesterday");


    //Create Dimension object
    $dimensionPageTitle = new Google_Service_AnalyticsReporting_Dimension();
    $dimensionCategory = new Google_Service_AnalyticsReporting_Dimension();
    $dimensionCategory->setName('ga:dimension1');

//    $dimension->setName('ga:pagePath');
    $dimensionPageTitle->setName('ga:pageTitle');

    //Dimension Filter
    $dimensionFilter = new Google_Service_AnalyticsReporting_DimensionFilter();
    $dimensionFilter->setDimensionName("ga:dimension1");
    $dimensionFilter->setOperator("REGEXP");
    $category_regex = '^\s'.$category.'\s$';
    $dimensionFilter->setExpressions(array($category_regex));

    $dimensionFilterClause = new Google_Service_AnalyticsReporting_DimensionFilterClause();
    $dimensionFilterClause->setFilters($dimensionFilter);


    // Create the Metrics object.
    $metrics = new Google_Service_AnalyticsReporting_Metric();
    $metrics->setExpression("ga:pageViews");
    $metrics->setAlias("pageViews");

    $ordering = new Google_Service_AnalyticsReporting_OrderBy();
    $ordering->setFieldName("ga:pageviews");
    $ordering->setOrderType("VALUE");
    $ordering->setSortOrder("DESCENDING");

    // Create the ReportRequest object.
    $request = new Google_Service_AnalyticsReporting_ReportRequest();
    $request->setViewId($VIEW_ID);
    $request->setDateRanges($dateRange);
    $request->setDimensions([$dimensionPageTitle,$dimensionCategory]);
    $request->setDimensionFilterClauses($dimensionFilterClause);
    $request->setMetrics(array($metrics));
    $request->setOrderBys($ordering);
    $request->setPageSize(1);

    $body = new Google_Service_AnalyticsReporting_GetReportsRequest();
    $body->setReportRequests( array( $request) );
    return $analytics->reports->batchGet( $body );
}


/**
 * Parses and prints the Analytics Reporting API V4 response.
 *
 * @param An Analytics Reporting API V4 response.
 */

function printResults($reports){
    for ( $reportIndex = 0; $reportIndex < count( $reports ); $reportIndex++ ) {
        $report = $reports[ $reportIndex ];//get 1 report
        $header = $report->getColumnHeader();
        $dimensionHeaders = $header->getDimensions();
        $metricHeaders = $header->getMetricHeader()->getMetricHeaderEntries();

        $rows = $report->getData()->getRows(); //get data rows of the table
        $categoryArr = array();
        for ( $rowIndex = 0; $rowIndex < count($rows); $rowIndex++) {
            $row = $rows[ $rowIndex ];//get 1 row
            $dimensions = $row->getDimensions();
            $metrics = $row->getMetrics();

            $categoryArr["category"] = $dimensions[1];
            $categoryArr["pageTitle"] = $dimensions[0];
            $categoryArr["pageViews"] = $metrics[0]->getValues()[0];
            $pageTitle = $dimensions[0];
            $replace_what =" | Indian Journal of Medical Ethics";
            return str_replace($replace_what,"",$pageTitle);
        }
    }
}
