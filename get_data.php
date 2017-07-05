<?php
include('bq.php');
$bq = new Bigquery();
$bq->connect();
$res = $bq->query("
#standardSQL
SELECT
  SUM(IF (installed_status = 1 AND status='current',
      1,
      0)) AS all_installs,
  SUM(IF (installed_status = 1 AND status='current'
      AND CAST(first_print AS date)>=DATE_ADD(CURRENT_DATE(),INTERVAL -7 DAY),
      1,
      0)) AS current_7_installs,
  SUM(IF (installed_status = 1 AND status='current'
      AND CAST(first_print AS date) BETWEEN DATE_ADD(CURRENT_DATE(),INTERVAL -14 DAY)
      AND DATE_ADD(CURRENT_DATE(),INTERVAL -7 DAY),
      1,
      0)) AS previous_7_installs,
  SUM(IF (installed_status = 1 AND status='current'
      AND CAST(first_print AS date)>=DATE_ADD(CURRENT_DATE(),INTERVAL -30 DAY),
      1,
      0)) AS current_30_installs,
  SUM(IF (installed_status = 1 AND status='current'
      AND CAST(first_print AS date) BETWEEN DATE_ADD(CURRENT_DATE(),INTERVAL -60 DAY)
      AND DATE_ADD(CURRENT_DATE(),INTERVAL -30 DAY),
      1,
      0)) AS previous_30_installs
FROM
  smart_analytics.receipt_deployment rd
WHERE
  NOT REGEXP_CONTAINS(rd.store_name,r'(?i)(demo|test)')
");
foreach ($res as $row)
{
    $total_installs = $row[0];
    $last_7_days = $row[1];
    $prior_7_days = $row[2];
    $last_30_days = $row[3];
    $prior_30_days = $row[4];
}

$res = $bq->query("
#standardSQL
SELECT
COUNT(DISTINCT entity_id)
FROM
`smart_analytics.transactions_meta_*`
WHERE
_TABLE_SUFFIX BETWEEN '".date('Ymd',strtotime('-6 days'))."'
AND '".date('Ymd')."'
");
foreach ($res as $row)
{
    $total_installs = $row[0];
}
// Get "live" data for the last 10 hours to find any entities that don't exist in the daily receipt deployment and add them into the total installs number
$res = $bq->query("
select exact_count_distinct(entity_id) from [ingestion.transactions_02@-3600000--1] where entity_id not in (select id from [smart_analytics.receipt_deployment] )
");
foreach ($res as $row)
{
    $total_installs += $row[0];
}

$weekly = round((($last_7_days - $prior_7_days) / $prior_7_days) * 100);
$monthly = round((($last_30_days - $prior_30_days) / $prior_30_days) * 100);
echo json_encode(Array(
    'total_installs' => number_format($total_installs)
    , 'weekly'      => abs($weekly)
    , 'monthly'     => abs($monthly)
    , 'last_7_days' => $last_7_days
    , 'prior_7_days' => $prior_7_days
    , 'last_30_days' => $last_30_days
    , 'prior_30_days' => $prior_30_days
    , 'weekly_dir'      => updown($weekly)
    , 'monthly_dir'     => updown($monthly)
));

function updown($num) 
{
    return ($num >= 0)?'up':'down';
}
