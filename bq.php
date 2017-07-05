<?php
set_include_path('/var/www/analytics/application/third_party/google-api-php-client-2.0.0-RC4/src');
putenv('GOOGLE_APPLICATION_CREDENTIALS=/var/www/analytics/application/third_party/google-api-php-client-2.0.0-RC4/service-account.json');
include('/var/www/analytics/application/third_party/google-api-php-client-2.0.0-RC4/vendor/autoload.php');


class Bigquery
{
    public function __construct()
    {
        // Set Project
        $this->project_id = 'kinetic-anvil-797';
    }

    function connect()
    {
        $this->client = new Google_Client();
        $this->client->useApplicationDefaultCredentials();
        $this->client->addScope(Google_Service_Bigquery::BIGQUERY);
        $this->client->addScope(Array('https://www.googleapis.com/auth/drive','https://www.googleapis.com/auth/drive.readonly','https://www.googleapis.com/auth/drive.metadata'));
        $this->service = new Google_Service_Bigquery($this->client);

        $this->job = new Google_Service_Bigquery_Job();
        $this->config = new Google_Service_Bigquery_JobConfiguration();
        $this->queryConfig = new Google_Service_Bigquery_JobConfigurationQuery();
        $this->config->setQuery($this->queryConfig);

        $this->job->setConfiguration($this->config);
    }

    function list_jobs()
    {
        $job_list = $this->service->jobs->listJobs($this->project_id, Array('maxResults' => 500));
        return $job_list;
    }

    function query($__sql, $__legacy = FALSE)
    {
        $return_data = Array();
        $this->queryConfig->setQuery($__sql);
        try {
        $res            = $this->service->jobs->insert($this->project_id,$this->job);
        $jr             = $res->getJobReference();
        $jobId          = $jr['jobId'];
        $rowsPerPage    = 5000;
        $pageToken      = null;
        $page_file      = 1;
        $timeout        = '120000';

        do 
        {
            $page = $this->service->jobs->getQueryResults($this->project_id, $jobId, array(
                'pageToken'     => $pageToken,
                'maxResults'    => $rowsPerPage,
                'timeoutMs'     => $timeout
            ));
            $rows = $page->getRows();
            if ($rows) 
            {
                $data = Array();
                foreach ($rows as $index => $row)
                {
                    $rec = $row['f'];
                    foreach ($rec as $col_index => $values)
                    {
                        $return_data[$page_file . '-' . $index][$col_index] = $rec[$col_index]['v'];
                    }
                }
                $pageToken = $page->getPageToken();
                $page_file++;
            }
        }
        while ($pageToken);
        return $return_data;
        } catch (Google_Service_Exception $e) {
            return $e->getMessage();
        }

    }
}

