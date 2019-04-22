<?php

// Load the Google API PHP Client Library.
require_once __DIR__ . '/googleAPI/vendor/autoload.php';
$articleName = '/articles/';
if(isset($_GET['article_name'])){

    $articleName .= $_GET['article_name'];
}else{
    $message['response'] = "Parameter missing";
    echo json_encode($message);
    exit();
}
$articleName .= '/';

$analytics = initializeAnalytics();
$response = getReport($analytics,$articleName);
//var_dump($response);
printResults($response);


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
function getReport($analytics,$articleName) {

    // Replace with your view ID, for example XXXX.
    $VIEW_ID = "74396426";

    // Create the DateRange object.
    $dateRange = new Google_Service_AnalyticsReporting_DateRange();
    $dateRange->setStartDate("2016-01-01");
    $dateRange->setEndDate("yesterday");

    //Create Dimension object
    $dimension = new Google_Service_AnalyticsReporting_Dimension();
    $dimension->setName('ga:pagePath');


    //Dimension Filter
    $dimensionFilter = new Google_Service_AnalyticsReporting_DimensionFilter();
    $dimensionFilter->setDimensionName("ga:pagePath");
    $dimensionFilter->setOperator("EXACT");
    $dimensionFilter->setExpressions(array($articleName));

    $dimensionFilterClause = new Google_Service_AnalyticsReporting_DimensionFilterClause();
    $dimensionFilterClause->setFilters($dimensionFilter);

    // Create the Metrics object.
    $sessions = new Google_Service_AnalyticsReporting_Metric();
    $sessions->setExpression("ga:pageViews");
    $sessions->setAlias("PageViews");

    // Create the ReportRequest object.
    $request = new Google_Service_AnalyticsReporting_ReportRequest();
    $request->setViewId($VIEW_ID);
    $request->setDateRanges($dateRange);
    $request->setMetrics(array($sessions));
    $request->setDimensions($dimension);
    $request->setDimensionFilterClauses($dimensionFilterClause);


    $body = new Google_Service_AnalyticsReporting_GetReportsRequest();
    $body->setReportRequests( array( $request) );
    return $analytics->reports->batchGet( $body );
}


/**
 * Parses and prints the Analytics Reporting API V4 response.
 *
 * @param An Analytics Reporting API V4 response.
 */
function printResults($reports) {
    for ( $reportIndex = 0; $reportIndex < count( $reports ); $reportIndex++ ) {
        $report = $reports[ $reportIndex ];
        $header = $report->getColumnHeader();
        $dimensionHeaders = $header->getDimensions();
        $metricHeaders = $header->getMetricHeader()->getMetricHeaderEntries();
        $rows = $report->getData()->getRows();

        for ( $rowIndex = 0; $rowIndex < count($rows); $rowIndex++) {
            $row = $rows[ $rowIndex ];
            $dimensions = $row->getDimensions();
            $metrics = $row->getMetrics();
//            for ($i = 0; $i < count($dimensionHeaders) && $i < count($dimensions); $i++) {
//                print($dimensionHeaders[$i] . ": " . $dimensions[$i] . "\n");
//            }

            for ($j = 0; $j < count($metrics); $j++) {
                $values = $metrics[$j]->getValues();
                for ($k = 0; $k < count($values); $k++) {
                    $entry = $metricHeaders[$k];
                    $pageViews[$entry->getName()] = $values[$k];
                }
            }
        }
    }
    echo json_encode($pageViews);
}
