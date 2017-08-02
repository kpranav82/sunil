<?php //echo 'die'; die; ?>
<link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">

<style type="text/css">

.toggle_inner{
float: left;
width: 100%;
text-align: right;
}

.like_dislike_btn_group ul li{
  position: relative;
}
  .like_up {
    display: none;
    float: left;
    width: 150px;
    background-color: #fff;
    position: absolute;
    top: 29px;
    z-index: 9;
    border: 1px solid #449f20;
padding: 5px;

}


    .like_down{
    display: none;
    float: left;
    width: 150px;
    background-color: #fff;
    position: absolute;
    top: 29px;
    z-index: 9;
    left: 10%;
    border: 1px solid #449f20;
    padding: 5px;

  }
  .like_up_btn{
    float: left;width: 100%;
    text-align: center;
    margin-top: 8px;
  }
  .like_up_btn a{
   max-width: 80px;
   font-size: 13px;
   float: none !important;
   background-color: #449f20 !important;
   padding: 5px !important;
   color: #fff !important;
   display: block;
   margin: 0 auto !important;
  }
  .like_up_wrap{
    float: left;width: 100%;
  }
  .like_up_wrap h4{
    float: left;width: 100%;
    margin-bottom: 8px;
  }







.read-more-state {
  display: none;
}

.read-more-target {
  opacity: 0;
  max-height: 0;
  font-size: 0;
  transition: .25s ease;
}

.read-more-state:checked ~ .read-more-wrap .read-more-target {
  opacity: 1;
  font-size: inherit;
  max-height: 999em;
}

.read-more-state ~ .read-more-trigger:before {
  content: 'Show more';
}

.read-more-state:checked ~ .read-more-trigger:before {
  content: 'Show less';
}

.read-more-trigger {
  cursor: pointer;
  display: inline-block;
  padding: 0 .5em;
  color: #666;
  font-size: .9em;
  line-height: 2;
  border: 1px solid #ddd;
  border-radius: .25em;
}




@media (min-width: 1024px){
.cust_bot_wrap{
  display: block ;
}
}

</style>




<?php
session_start();
header("Content-Type: text/plain; charset=ISO-8859-1");
include_once('clientapplicationtop.php');        // Including Client Application Top
include_once('includes/classes/class.coupons.php');      // Including the coupon class object
include_once('includes/classes/class.stores.php');      //  Including the store class object

$st_cat_id = $_POST['st_cat_id'];          // Get selected store category from Left sidebar
$st_zip_code = $_POST['st_zip_code'];         // Get zipcode from refine form
$st_miles = $_POST['st_miles'];
$order_by = $_REQUEST['order_by'];
$search_key = $_REQUEST['search_key'];

$_SESSION["st_cat_id"] = $st_cat_id;
$_SESSION["st_miles"] = $st_miles;
$_SESSION["st_zip_code"] = $st_zip_code;
$_SESSION["order_by"] = $order_by;
$_SESSION["search_key"] = $search_key;
$_SESSION["lastpage"] = $_REQUEST['page'];


// Get miles from refine form
//$st_miles = ( ($st_miles*1600)+1 );                   // Convert miles into values for googlemap
?>
<style>
    /** Social Button CSS **/

    .share-btn {
        display: inline-block;
        color: #ffffff;
        border: none;
        padding: 0.5em;
        width: 4em;
        box-shadow: 0 2px 0 0 rgba(0,0,0,0.2);
        outline: none;
        text-align: center;
    }

    .share-btn:hover {
        color: #eeeeee;
    }

    .share-btn:active {
        position: relative;
        top: 2px;
        box-shadow: none;
        color: #e2e2e2;
        outline: none;
    }

    .share-btn.email       { background: #444444; }
    .empty {
        clear: both;
        color: #224330;
        font-family: Microsoft Jhenghei UI;
        font-size: 24px;
        padding: 50px 30px;
        text-align: center;
    }
    .social{
        width:72px;
        padding-right:0;
    }
    .email_share{
        widht:36px;
        float:left;
        padding-right:5;
    }
    .at-svc-email{
        display:none !important;
    }
</style>
<?php
/* Calculate Distances using google map api - currently not using */

function calculate_distances_zip($st_zip_code, $zip_latitude, $zip_longitude, $st_miles) {
    $fst_zip_code = $st_zip_code;
    $fzip_latitude = $zip_latitude;
    $fzip_longitude = $zip_longitude;
    $fst_miles = $st_miles;
    $fd_stores_ids = array();

    $ftemp_query2 = "SELECT * FROM stores WHERE stores_status = 'y'";
    $sd_results = mysql_query($ftemp_query2) or die(mysql_error());
    while ($stores_distances = mysql_fetch_assoc($sd_results)) {
        if ($fst_zip_code == $stores_distances['stores_zip']) {
            continue;
        }
        $stores_latitude = $stores_distances['stores_latitude'];
        $stores_longitude = $stores_distances['stores_longitude'];
        //echo $stores_distances['stores_id'].'<br>';
        $address = "http://maps.googleapis.com/maps/api/distancematrix/json?origins=" . $fzip_latitude . "," . $fzip_longitude . "&destinations=" . $stores_latitude . "," . $stores_longitude . "&units=imperial";
        $json_decode_info = json_decode(file_get_contents($address));
        //print_r($json_decode_info);

        if ($json_decode_info->status == 'OK') {
            //echo '<span style="color: #fff;background: #80B935;display:block">STATUS_OK_1</span>';
            if ($json_decode_info->rows[0]->elements[0]->status == 'OK') {
                //echo '<span style="color: #fff;background: #80B935;display:block">STATUS_OK_2</span>';
                if ($json_decode_info->rows[0]->elements[0]->distance->value < $fst_miles) {
                    //echo '<span style="color: #fff;background: #80B935;display:block">Within 5 miles</span>';
                    $fd_stores_ids[] = $stores_distances['stores_id'];
                }
            }
        }
    }
    return $fd_stores_ids;
}

/* Using google map api */

function calculate_distances_zipcat($st_zip_code, $zip_latitude, $zip_longitude, $st_miles, $stores_ids) {
    $fst_zip_code = $st_zip_code;
    $fzip_latitude = $zip_latitude;
    $fzip_longitude = $zip_longitude;
    $fst_miles = $st_miles;
    $fstores_ids = $stores_ids;
    $fd_stores_ids = array();

    $fids = implode(',', $fstores_ids);
    $ftemp_query2 = "SELECT * FROM stores WHERE stores_id IN ($fids) AND stores_status = 'y'";
    $sd_results = mysql_query($ftemp_query2) or die(mysql_error());
    while ($stores_distances = mysql_fetch_assoc($sd_results)) {
        if ($fst_zip_code == $stores_distances['stores_zip']) {
            continue;
        }
        $stores_latitude = $stores_distances['stores_latitude'];
        $stores_longitude = $stores_distances['stores_longitude'];
        //echo $stores_distances['stores_id'].'<br>';
        $address = "http://maps.googleapis.com/maps/api/distancematrix/json?origins=" . $fzip_latitude . "," . $fzip_longitude . "&destinations=" . $stores_latitude . "," . $stores_longitude . "&units=imperial";
        $json_decode_info = json_decode(file_get_contents($address));
        //print_r($json_decode_info);

        if ($json_decode_info->status == 'OK') {
            //echo '<span style="color: #fff;background: #80B935;display:block">STATUS_OK_1</span>';
            if ($json_decode_info->rows[0]->elements[0]->status == 'OK') {
                //echo '<span style="color: #fff;background: #80B935;display:block">STATUS_OK_2</span>';
                if ($json_decode_info->rows[0]->elements[0]->distance->value < $fst_miles) {
                    //echo '<span style="color: #fff;background: #80B935;display:block">Within 5 miles</span>';
                    $fd_stores_ids[] = $stores_distances['stores_id'];
                }
            }
        }
    }
    //print_r($fd_stores_ids);
    return $fd_stores_ids;
}

/* Calculate Distances using formula */
/* function from_calculate_distances_zip($st_zip_code,$zip_latitude,$zip_longitude,$st_miles) {

  $fst_zip_code   = $st_zip_code;
  $fzip_latitude  = $zip_latitude;
  $fzip_longitude = $zip_longitude;
  $fst_miles    = $st_miles;
  $unit       = 'M';
  $fd_stores_ids  = array();

  $ftemp_query2 = "SELECT * FROM stores WHERE stores_status = 'y'";
  $sd_results   = mysql_query($ftemp_query2) or die(mysql_error());
  while ($stores_distances = mysql_fetch_assoc($sd_results)) {
  if($fst_zip_code == $stores_distances['stores_zip'] ){
  continue;
  }
  $stores_latitude = $stores_distances['stores_latitude'];
  $stores_longitude = $stores_distances['stores_longitude'];
  if( ($stores_latitude == 0) && ($stores_longitude == 0) ){
  continue;
  }
  $theta = $fzip_longitude - $stores_longitude;
  $dist = sin(deg2rad($fzip_latitude)) * sin(deg2rad($stores_latitude)) +  cos(deg2rad($fzip_latitude)) * cos(deg2rad($stores_latitude)) * cos(deg2rad($theta));
  $dist = acos($dist);
  $dist = rad2deg($dist);
  $miles = $dist * 60 * 1.1515;
  $unit = strtoupper($unit);

  if ($unit == "K") {
  return ($miles * 1.609344);
  }else if ($unit == "N") {
  return ($miles * 0.8684);
  } else {
  if( $miles < $fst_miles ){
  $fd_stores_ids[] = $stores_distances['stores_id'];
  }
  }
  }
  return $fd_stores_ids;
  }
  function from_calculate_distances_zipcat($st_zip_code,$zip_latitude,$zip_longitude,$st_miles,$stores_ids) {

  $fst_zip_code   = $st_zip_code;
  $fzip_latitude  = $zip_latitude;
  $fzip_longitude = $zip_longitude;
  $fst_miles    = $st_miles;
  $fstores_ids  = $stores_ids;
  $unit       = 'M';
  $fd_stores_ids  = array();

  $fids = implode(',', $fstores_ids);
  $ftemp_query2 = "SELECT * FROM stores WHERE stores_id IN ($fids) AND stores_status = 'y'";
  $sd_results   = mysql_query($ftemp_query2) or die(mysql_error());
  while ($stores_distances = mysql_fetch_assoc($sd_results)) {
  if($fst_zip_code == $stores_distances['stores_zip'] ){
  continue;
  }
  $stores_latitude = $stores_distances['stores_latitude'];
  $stores_longitude = $stores_distances['stores_longitude'];
  if( ($stores_latitude == 0) && ($stores_longitude == 0) ){
  continue;
  }
  $theta = $fzip_longitude - $stores_longitude;
  $dist = sin(deg2rad($fzip_latitude)) * sin(deg2rad($stores_latitude)) +  cos(deg2rad($fzip_latitude)) * cos(deg2rad($stores_latitude)) * cos(deg2rad($theta));
  $dist = acos($dist);
  $dist = rad2deg($dist);
  $miles = $dist * 60 * 1.1515;
  $unit = strtoupper($unit);

  if ($unit == "K") {
  return ($miles * 1.609344);
  }else if ($unit == "N") {
  return ($miles * 0.8684);
  } else {
  if( $miles < $fst_miles ){
  $fd_stores_ids[] = $stores_distances['stores_id'];
  }
  }
  }
  return $fd_stores_ids;
  } */


/* Calculate Distances using formula */

function cal_distances($st_zip_code, $st_miles, $ids) {
    //Get latitute and longitude by GoogleAPI on the base of zipcode
    $address = "https://maps.googleapis.com/maps/api/geocode/json?address=" . $st_zip_code . "&sensor=true";
    $json_decode_info = json_decode(file_get_contents($address));
    if ($json_decode_info->status == 'OK') {
        $zip_latitude = $json_decode_info->results[0]->geometry->location->lat;
        $zip_longitude = $json_decode_info->results[0]->geometry->location->lng;
    } else {
        //If no response from GoogleAPI, then get stores by zipcode on the base of above ids for latitude and longitude
        $ftemp_query = "SELECT * FROM stores WHERE stores_zip = '$st_zip_code' AND stores_id IN ($ids) AND stores_status = 'y'";
        $ftemp_sids_results = mysql_query($ftemp_query) or die(mysql_error());
        $records = mysql_num_rows($ftemp_sids_results);
        if ($records == 0) {
            $ret['type'] = 'error';
            $ret['response'] = '';
        } else {
            while ($zip_latlong = mysql_fetch_assoc($ftemp_sids_results)) {
                $zip_latitude = $zip_latlong['stores_latitude'];
                $zip_longitude = $zip_latlong['stores_longitude'];
                //If gets latitude and longitude then break loop
                if ($zip_latitude != 0 && $zip_longitude != 0) {
                    break;
                }
            }
        }
    }




    $fst_zip_code = $st_zip_code;
    $fzip_latitude = $zip_latitude;
    $fzip_longitude = $zip_longitude;
    $fst_miles = $st_miles;
    $fids = $ids;
    $unit = 'M';
    $fd_stores_ids = array();

    $ftemp_query2 = "SELECT * FROM stores WHERE stores_id IN ($fids) AND stores_status = 'Y'";
    $sd_results = mysql_query($ftemp_query2) or die(mysql_error());
    while ($stores_distances = mysql_fetch_assoc($sd_results)) {
        /* if($fst_zip_code == $stores_distances['stores_zip'] ){
          continue;
          } */
        $stores_latitude = $stores_distances['stores_latitude'];
        $stores_longitude = $stores_distances['stores_longitude'];
        if (($stores_latitude == 0) && ($stores_longitude == 0)) {
            continue;
        }
        $theta = $fzip_longitude - $stores_longitude;
        $dist = sin(deg2rad($fzip_latitude)) * sin(deg2rad($stores_latitude)) + cos(deg2rad($fzip_latitude)) * cos(deg2rad($stores_latitude)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        $unit = strtoupper($unit);

        if ($unit == "K") {
            return ($miles * 1.609344);
        } else if ($unit == "N") {
            return ($miles * 0.8684);
        } else {
            if ($miles < $fst_miles) {
                $fd_stores_ids[] = $stores_distances['stores_id'];
                $fd_stores_ids_dist[$stores_distances['stores_id']] = $miles;
            }
        }
    }
    if (!empty($fd_stores_ids)) {
        //rsort($fd_stores_ids);
        asort($fd_stores_ids_dist);
        $distance = $fd_stores_ids_dist;
        $ids = implode(',', $fd_stores_ids);
        $ret['type'] = 'ids';
        $ret['response'] = $ids;
        $ret['ids_distance'] = $distance;
    } else {
        $ret['type'] = 'error';
        $ret['response'] = '';
    }
    return $ret;
}

/* Start From Here */
$stores_ids = array();
$stores_ids2 = array();
$stores_ids3 = array();
$zip_stores_ids = array();
$ids = '';
$records = 0;
$zip_latitude = '';
$zip_longitude = '';

$response = array();

function get_storeids_have_lcoupons() {
    $ret = array();
    //First get store_ids that have localcoupons from store_localcoupon
    $temp_query = "SELECT DISTINCT stores_id FROM store_localcoupon";
    $temp_sids_results = mysql_query($temp_query) or die(mysql_error());
    $records = mysql_num_rows($temp_sids_results);
    if ($records == 0) {
        $ret['type'] = 'error';
        $ret['response'] = '';
    } else {
        while ($temp_sids = mysql_fetch_assoc($temp_sids_results)) {
            $stores_ids[] = $temp_sids['stores_id'];
        }
        if (!empty($stores_ids)) {
            //rsort($stores_ids);
            $ids = implode(',', $stores_ids);
            $ret['type'] = 'ids';
            $ret['response'] = $ids;
        } else {
            $ret['type'] = 'error';
            $ret['response'] = '';
        }
    }
    return $ret;
}

function get_storeids_by_category($ids, $st_cat_id) {
    //Get store_ids by selected category
    $temp_query2 = "SELECT DISTINCT store_category_sid FROM store_category WHERE store_category_sid IN ($ids) AND store_category_cid = '$st_cat_id'";
    $temp_sids_results2 = mysql_query($temp_query2) or die(mysql_error());
    $records = mysql_num_rows($temp_sids_results2);
    if ($records == 0) {
        $ret['type'] = 'error';
        $ret['response'] = '';
    } else {
        while ($store_ids2 = mysql_fetch_assoc($temp_sids_results2)) {
            $stores_ids2[] = $store_ids2['store_category_sid'];
        }
        if (!empty($stores_ids2)) {
            //rsort($stores_ids2);
            $ids = implode(',', $stores_ids2);
            $ret['type'] = 'ids';
            $ret['response'] = $ids;
        } else {
            $ret['type'] = 'error';
            $ret['response'] = '';
        }
    }
    return $ret;
}
//Get the store images
function fetch_store_img($storeid = 0) {
    $fetchQry = "SELECT * FROM stores_images WHERE  stores_id=" . $storeid . "";
    $fetData = mysql_query($fetchQry);
    return($fetData);
}

function get_ordered_store_by_coupon($ids) {
    $temp_query = "SELECT * FROM stores as a  
          LEFT JOIN store_localcoupon as b on a.stores_id = b.stores_id
          LEFT JOIN localcoupons as c on b.localcoupons_id = c.localcoupons_id
          WHERE a.stores_id IN ($ids) AND stores_status = 'y' ORDER BY b.localcoupons_id DESC";
    $temp_sids_results = mysql_query($temp_query) or die(mysql_error());
    $records = mysql_num_rows($temp_sids_results);
    if ($records == 0) {
        $ret['type'] = 'error';
        $ret['response'] = '';
    } else {
        $stores_ids = array();
        while ($temp_sids = mysql_fetch_assoc($temp_sids_results)) {
            if (!in_array($temp_sids['stores_id'], $stores_ids)) {
                $stores_ids[] = $temp_sids['stores_id'];
            }
        }
        if (!empty($stores_ids)) {
            //rsort($stores_ids);
            $ids = implode(',', $stores_ids);
            $ret['type'] = 'ids';
            $ret['response'] = $ids;
        } else {
            $ret['type'] = 'error';
            $ret['response'] = '';
        }
    }
    return $ret;
}

$query = "SELECT * FROM messages WHERE message_title = 'local_coupon_no_record_message' ";
$error_results = mysql_query($query) or die(mysql_error());

/* $no_coupon_message = '<div class="empty">Sorry, we currently have no listings in this zipcode.</div>'; */
while ($deals = mysql_fetch_assoc($error_results)) {
    $no_coupon_message = '<div class="empty">' . $deals['message_detail'] . '</div>';
}
//If both zipcode and category are selected
if (($st_cat_id != '') && ($st_zip_code != '')) {
    //Get store_ids that have localcoupons
    $response = get_storeids_have_lcoupons();
    if ($response['type'] == 'error') {
        echo $no_coupon_message;
        exit();
    } else {
        $ids = $response['response']; //ids have local coupons
        $response = get_storeids_by_category($ids, $st_cat_id);

        if ($response['type'] == 'error') {
            echo $no_coupon_message;
            exit();
        } else {
            $ids = $response['response']; //ids have local coupons and in selected category
            //Call the function to calculate distance. Returns successed Ids.
            $response = cal_distances($st_zip_code, $st_miles, $ids);
            if ($response['type'] == 'error') {
                echo $no_coupon_message;
                exit();
            } else {
                $ids = $response['response']; //ids have local coupons and in selected miles by selected zip code
                $ids_distance = $response['ids_distance']; //ids have local coupons and in selected miles by selected zip code with distance
                $ids_length = count($ids_distance);
                foreach ($ids_distance as $key => $value) {
                    if ($ids_length > 1) {
                        $ids_by_sort .= $key . ',';
                    } else {
                        $ids_by_sort .= $key;
                    }
                    $ids_length--;
                }
                if ($order_by == 'recent') {
                    $response_new = get_ordered_store_by_coupon($ids);
                    $ids_by_sort = $response_new['response'];
                }
                if ($order_by == 'alpha') {
                    $query = "SELECT * FROM stores WHERE stores_id IN ($ids) AND stores_status = 'y' ORDER BY stores_name ASC";
                } else {
                    $query = "SELECT * FROM stores WHERE stores_id IN ($ids) AND stores_status = 'y' ORDER BY FIELD(stores_id,$ids_by_sort)";
                }
            }
        }
    }


    /*  $temp_query   = "SELECT store_category_sid FROM store_category WHERE store_category_cid = '$st_cat_id'";            //Get store ids by selected category
      $sid_results  = mysql_query($temp_query) or die(mysql_error());

      while ($store_ids = mysql_fetch_assoc($sid_results)) {
      $stores_ids[] = $store_ids['store_category_sid'];
      }
      if($stores_ids){
      $ids = implode(',', $stores_ids);
      }else {
      echo '<div class="empty">Sorry, we currently have no listings in this zipcode.</div>';
      exit();
      }

      $temp_query2  = "SELECT * FROM stores WHERE stores_zip = '$st_zip_code' AND stores_id IN ($ids) AND stores_status = 'y'";     //Then get stores by zip code on the base of above ids
      $temp_s_results = mysql_query($temp_query2) or die(mysql_error());
      $records    = mysql_num_rows($temp_s_results);

      if($records == 0){
      echo '<div class="empty">Sorry, we currently have no listings in this zipcode.</div>';
      exit();
      }else{
      while ($zip_latlong = mysql_fetch_assoc($temp_s_results)) {
      $zip_latitude = $zip_latlong['stores_latitude'];
      $zip_longitude = $zip_latlong['stores_longitude'];
      $stores_ids2[] = $zip_latlong['stores_id'];
      }
      //$stores_ids3 = calculate_distances_zipcat($st_zip_code,$zip_latitude,$zip_longitude,$st_miles,$stores_ids);               //Call the function to calculate distance. Returns successed Ids.
      $stores_ids3 = from_calculate_distances_zipcat($st_zip_code,$zip_latitude,$zip_longitude,$st_miles,$stores_ids);                //Call the function to calculate distance. Returns successed Ids.
      $ids = implode(',', $stores_ids2);
      if( !empty($stores_ids3) ){
      $ids .= ',';
      $ids .= implode(',', $stores_ids3);
      }

      $query  = "SELECT * FROM stores WHERE stores_id IN ($ids) AND stores_status = 'y'";
      } */
}
//If only zipcode is selected
elseif (($st_cat_id == '') && ($st_zip_code != '')) {
    //Get store_ids that have localcoupons
    $response = get_storeids_have_lcoupons();
    if ($response['type'] == 'error') {
        echo $no_coupon_message;
        exit();
    } else {
        $ids = $response['response']; //ids have local coupons
        //Call the function to calculate distance. Returns successed Ids.
        $response = cal_distances($st_zip_code, $st_miles, $ids);
        if ($response['type'] == 'error') {
            echo $no_coupon_message;
            exit();
        } else {
            $ids = $response['response']; //ids have local coupons and in selected miles by selected zip code
            $ids_distance = $response['ids_distance']; //ids have local coupons and in selected miles by selected zip code with distance
            $ids_length = count($ids_distance);
            foreach ($ids_distance as $key => $value) {
                if ($ids_length > 1) {
                    $ids_by_sort .= $key . ',';
                } else {
                    $ids_by_sort .= $key;
                }
                $ids_length--;
            }
            if ($order_by == 'recent') {
                $response_new = get_ordered_store_by_coupon($ids);
                $ids_by_sort = $response_new['response'];
            }
            if ($order_by == 'alpha') {
                $query = "SELECT * FROM stores WHERE stores_id IN ($ids) AND stores_status = 'y' ORDER BY stores_name ASC";
            } else {
                $query = "SELECT * FROM stores WHERE stores_id IN ($ids) AND stores_status = 'y' ORDER BY FIELD(stores_id,$ids_by_sort)";
            }
        }
    }

    //Get store ids by selected zipcode
    /* $temp_query2   = "SELECT * FROM stores WHERE stores_zip = '$st_zip_code' AND stores_status = 'y'";               
      $temp_s_results = mysql_query($temp_query2) or die(mysql_error());
      $records    = mysql_num_rows($temp_s_results);

      if($records == 0){
      echo '<div class="empty">Sorry, we currently have no listings in this zipcode.</div>';
      exit();
      }else{
      while ($zip_latlong = mysql_fetch_assoc($temp_s_results)) {
      $zip_latitude = $zip_latlong['stores_latitude'];
      $zip_longitude = $zip_latlong['stores_longitude'];
      $stores_ids[] = $zip_latlong['stores_id'];
      }
      //Call the function to calculate distance. Returns successed Ids.
      $stores_ids2 = from_calculate_distances_zip($st_zip_code,$zip_latitude,$zip_longitude,$st_miles);
      $ids = implode(',', $stores_ids);
      if( !empty($stores_ids2) ){
      $ids .= ',';
      $ids .= implode(',', $stores_ids2);
      }

      $query  = "SELECT * FROM stores WHERE stores_id IN ($ids) AND stores_status = 'y'";
      } */
}
//If only category is slected
elseif (($st_cat_id != '') && ($st_zip_code == '')) {

    //Get store_ids that have localcoupons
    $response = get_storeids_have_lcoupons();
    if ($response['type'] == 'error') {
        echo $no_coupon_message;
        exit();
    } else {
        $ids = $response['response']; //ids have local coupons
        $response = get_storeids_by_category($ids, $st_cat_id);

        if ($response['type'] == 'error') {
            echo $no_coupon_message;
            exit();
        } else {
            $ids = $response['response']; //ids have local coupons and in selected category
            if ($order_by == 'recent') {
                $response_new = get_ordered_store_by_coupon($ids);
                $ids_by_sort = $response_new['response'];
            }
            if ($order_by == 'alpha') {
                $query = "SELECT * FROM stores WHERE stores_id IN ($ids) AND stores_status = 'y' ORDER BY stores_name ASC";
            } elseif ($order_by == 'recent') {
                $query = "SELECT * FROM stores WHERE stores_id IN ($ids) AND stores_status = 'y' ORDER BY FIELD(stores_id,$ids_by_sort)";
            } else {
                $query = "SELECT * FROM stores WHERE stores_id IN ($ids) AND stores_status = 'y' ORDER BY stores_id DESC";
            }
        }
    }
    //Get store_ids by selected category
    /* $temp_query  = "SELECT store_category_sid FROM store_category WHERE store_category_cid = '$st_cat_id'";
      $sid_results  = mysql_query($temp_query) or die(mysql_error());

      while ($store_ids = mysql_fetch_assoc($sid_results)) {
      $stores_ids[] = $store_ids['store_category_sid'];
      }
      if($stores_ids){
      $ids = implode(',', $stores_ids);
      }else {
      echo '<div class="empty">Sorry, we currently have no listings in this zipcode.</div>';
      exit();
      }

      $query    = "SELECT * FROM stores WHERE stores_id IN ($ids) AND stores_status = 'y'"; */         //Then get stores on the base of above ids
}
//default if nothing selected
else {
    /* $query = "SELECT * FROM stores WHERE stores_status = 'y'"; */
    $response = get_storeids_have_lcoupons();
    if ($response['type'] == 'error') {
        echo $no_coupon_message;
        exit();
    } else {
        $ids = $response['response']; //ids have local coupons
        if ($order_by == 'recent') {
            $response_new = get_ordered_store_by_coupon($ids);
            $ids_by_sort = $response_new['response'];
        }
        if ($order_by == 'alpha') {
            $query = "SELECT * FROM stores WHERE stores_id IN ($ids) AND stores_status = 'y' ORDER BY stores_name ASC";
        } elseif ($order_by == 'recent') {
            $query = "SELECT * FROM stores WHERE stores_id IN ($ids) AND stores_status = 'y' ORDER BY FIELD(stores_id,$ids_by_sort)";
        } else {
            $query = "SELECT * FROM stores WHERE stores_id IN ($ids) AND stores_status = 'y' ORDER BY stores_id DESC";
        }
    }
}


$records = 0;
//echo $_REQUEST['search'];
if($search_key != ''){
  $str_to_insert = "AND stores_name LIKE '%".$search_key."%' ";
  $pos = strpos($query,"ORDER BY");
  $query = substr_replace($query, $str_to_insert, $pos, 0);
}
//$query = $query.' limit 0,8';
//echo $query;
$s_results = mysql_query($query) or die(mysql_error());
$records = mysql_num_rows($s_results);
if ($records == 0) {
    echo $no_coupon_message;
    exit();
}
$i = 1;
$err_msg = true;
$page = $_REQUEST['page'];
$paged = 10;
if($_SESSION["pagevisit"] == "yes" && $_SESSION["lastvisit"] != '' && ($page == 1 || $page == '')){
  $paged = 10*($_SESSION["lastvisit"]);
  $_SESSION["pagevisit"] ='';
  $_SESSION["lastvisit"] = '';
}

if($page != ''){
   $limit = $paged * $page;
   $count = $paged * ($page - 1);
}else{
   $limit = $paged;
     $count = '';
}
$start = 1;
while ($stores = mysql_fetch_assoc($s_results)) {
  if($start <= $limit){
    $store_id = $stores['stores_id'];
    //Get from storefromfeeds table for going on next page
    $query = "SELECT storefromfeeds_id FROM storefromfeeds WHERE storefromfeeds_storeid = $store_id";
    $sfromfeeds_result = mysql_query($query) or die(mysql_error());
    $storefromfeeds_id = '';
    while ($storefromfeeds = mysql_fetch_assoc($sfromfeeds_result)) {
        $storefromfeeds_id = $storefromfeeds['storefromfeeds_id'];
    }
    ?>
    <?php
    $today = date('Y-m-d');
    if ($order_by == 'recent') {
        $query = "SELECT localcoupons_id FROM store_localcoupon WHERE stores_id = '$store_id' ORDER BY localcoupons_id DESC";
    } else {
        $query = "SELECT localcoupons_id FROM store_localcoupon WHERE stores_id = '$store_id' ";
    }
    $lid_result = mysql_query($query) or die(mysql_error());
    $coupons = '';
    $coupontype = '';
    $flag = 1;
    $new_data = array();
    $lid_result_new = mysql_query($query) or die(mysql_error());
    while ($localcoupons_id_new = mysql_fetch_assoc($lid_result_new)) {
        $localcoupons_ids = $localcoupons_id_new['localcoupons_id'];

        $query_new = "SELECT * FROM localcoupons WHERE localcoupons_id = $localcoupons_ids AND localcoupons_status='Y' AND DATE(localcoupons_enddate) >= '$today'";

        $l_result_new = mysql_query($query_new) or die(mysql_error());
        while ($localcoupons_new = mysql_fetch_assoc($l_result_new)) {
            $new_data[] = $localcoupons_new;
        }
    }
  if($new_data && count($new_data)>3){
    $start++;
    if($count != '' && $start <= $count+1){
      //continue;
      $flag = 2;
      $i = $i+count($new_data);
    }
    while ($localcoupons_id = mysql_fetch_assoc($lid_result)) {
        $localcoupons_ids = $localcoupons_id['localcoupons_id'];

        $query = "SELECT * FROM localcoupons WHERE localcoupons_id = $localcoupons_ids AND localcoupons_status='Y' AND DATE(localcoupons_enddate) >= '$today'";

        $l_result = mysql_query($query) or die(mysql_error());
        while ($localcoupons = mysql_fetch_assoc($l_result)) {
            if ($flag == 1) {
                if ($localcoupons['localcoupons_desc'] != '') {
                    $err_msg = false; ?>
                    <div class="pro_row">
                        <div class="str_box">
                            <div class="st_top">
                                <img src="images/st_top.gif">
                            </div>
                            <div class="st_mid">
                                <div class="st_mid_top">


 

                                    <div class="st_mid_top_left">

                                        <div class="sto_img">
                                           
                        <?php if(mysql_fetch_array(fetch_store_img($store_id))){
                        $light_box = 1;
                        ?>
                        <?php
                      
                          $store_Images = fetch_store_img($store_id);
                          while($row = mysql_fetch_array($store_Images)){ 
                          //print_r($row);
                            if ($row['image_thumb'] != ""){
                              ?>
                        <a class="" href="uploads/stores/<?php echo $row['image']; ?>" data-lightbox="store-set<?php echo $i; ?>" >
                          <?php if($light_box == 1){?>
                          <img src="uploads/stores/<?php echo $row['image_thumb']; ?>"/><br/>
                              <span class="click_img">Click On Image</span>
                              <?php if(isset($_GET['miles'])){ echo '<p class="miles_away">'.$_GET['miles'].' miles away</p>'; }
                              $st_img = 'http://couponzipcode.com/uploads/stores/'.$row['image_thumb'];
                           } ?>
                        </a>
                        <?php }else{ ?>
                        <img src="images/no-image_med.gif">
                              <?php
                              $st_img = 'http://couponzipcode.com/images/no-image_med.gif';
                            }
                        $light_box++; } ?>
                      <?php }else{?>
                      <img src="images/no-image_med.gif">
                        <?php if(isset($_GET['miles'])){ echo '<p class="miles_away">'.$_GET['miles'].' miles away</p>'; }
                              $st_img = 'http://couponzipcode.com/images/no-image_med.gif';
                       }?>
               
                                        </div>



                                        <div class="st_det">
                                            <div class="st_title"><strong><a class="dispdesc_<?php echo $i; ?>" href="store_details.php?storefromfeeds_id=<?php echo base64_encode($storefromfeeds_id); ?>&st_id=<?php echo base64_encode($store_id);
                                            if (!empty($ids_distance)) {
                                                echo '&miles=' . number_format($ids_distance[$store_id], 2, '.', '');
                                            } ?>">
                                            <?php echo $stores['stores_name']; ?></a></strong> 




                                            <span class="dolar_cust"> <?php
                                            //Miles away
                                            if (!empty($ids_distance)) 
                                            {
                                                $ids_distance[$store_id] = number_format($ids_distance[$store_id], 2, '.', '');
                                                echo  $ids_distance[$store_id];
                                            }
                                            ?>  
                                            </span> 
                                            </div> 


<!--like unlike -->


                                            <?php if (isset($_SESSION['loggedMemberID'])) { ?>
                                            <div class="like_dislike_btn_group">
                                            <ul>
                                                <li>
                                                    <a href="javascript:void(0);" onclick="like_stores('<?php echo $store_id; ?>');"> <i class="fa fa-thumbs-up like_data"></i></a>
                                                        <span id="count_like_<?php echo  $store_id; ?>"> 
                                                            <?php


                                                            //echo $store_id.'hawwwwwwww<br/>'; die;
                                                            $servername = "localhost";
                                                            $username = "root";
                                                            $password = "sunny007";
                                                            $db = "couponzipcode";
                                                            //$store_id = $_GET['id'];
                                                            //$user_id =$_GET['user_id'];
                                                            //echo $store_id; die;
                                                            // Create connection
                                                            $conn = new mysqli($servername, $username, $password, $db);
                                                            // Check connection




                                                            $sql = "SELECT * FROM like_dislike WHERE store_id='$store_id' AND status='1'";
                                                            //echo $sql; die;
                                                            $result =$conn->query($sql);
                                                            $count = $result->num_rows;
                                                            if ($result->num_rows > 0) {
                                                                while($row = $result->fetch_assoc()) {
                                                                     $count1  = $count;
                                                                }
                                                                echo $count1; 
                                                                //die;
                                                            }
                                                            else 
                                                            {
                                                              echo '0';
                                                            } ?>
                                                        </span>
                                                </li>
                                                <li>
                                                    <a href="JavaScript:void(0);" onclick="dislike_stores('<?php echo $store_id; ?>');"><i class="fa fa-thumbs-down dis_like_data" ></i></a>
                                                        <span id="count_dislike_<?php echo  $store_id; ?>"  >
                                                        <?php



                                                            //echo $store_id.'hawwwwwwww<br/>'; die;
                                                            $servername = "localhost";
                                                            $username = "root";
                                                            $password = "sunny007";
                                                            $db = "couponzipcode";
                                                            //$store_id = $_GET['id'];
                                                            //$user_id =$_GET['user_id'];
                                                            //echo $store_id; die;
                                                            // Create connection
                                                            $conn = new mysqli($servername, $username, $password, $db);
                                                            // Check connection




                                                            $sql = "SELECT * FROM like_dislike WHERE store_id='$store_id' AND status='0'";
                                                            //echo $sql; die;
                                                            $result =$conn->query($sql);
                                                            $count = $result->num_rows;
                                                            if ($result->num_rows > 0) {
                                                                while($row = $result->fetch_assoc()) {
                                                                     $count1  = $count;
                                                                }
                                                                echo $count1; 
                                                                //die;
                                                            } 
                                                            else 
                                                            {
                                                                echo '0';
                                                            } 
                                                        ?>


                                                        </span>

                                                </li>
                                            </ul>
                                            
                                            </div>
                                        <?php } ?>


<!-- with log in  ends  -->

<!-- witout log in  -->
  <?php if(empty($_SESSION['loggedMemberID'])) { ?>
              <div class="like_dislike_btn_group">
                          <ul>
                              <li>
                                    <a class="thumbs_up " href="javascript:void(0);"><i class="fa fa-thumbs-up like-button-view"></i></a>
          <!--  <div class="like_up">
            <div class="like_up_wrap">
            <h4>Like this?</h4>
             <small>Sign in to make your opinion count.</small>
              </div>
            <div class="like_up_btn"><a href="">SIGN IN</a></div>
           </div>
 -->



                                  <span id="count_like_<?php echo  $store_id; ?>"> 
<?php


//echo $store_id.'hawwwwwwww<br/>'; die;
$servername = "localhost";
$username = "root";
$password = "sunny007";
$db = "couponzipcode";
//$store_id = $_GET['id'];
//$user_id =$_GET['user_id'];
//echo $store_id; die;
// Create connection
$conn = new mysqli($servername, $username, $password, $db);
// Check connection




$sql = "SELECT * FROM like_dislike WHERE store_id='$store_id' AND status='1'";
//echo $sql; die;
$result =$conn->query($sql);
$count = $result->num_rows;
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
         $count1  = $count;
    }
    echo $count1; 
    //die;
} else {
  echo '0';
  } ?>
            </span>
          </li>
        <li>
            <a class="thumbs_down " href="JavaScript:void(0);" "><i class="fa fa-thumbs-down like-button-view" ></i></a>

        <!--   <div class="like_down">
            <div class="like_up_wrap">
            <h4>Don't like this?</h4>
             <small>Sign in to make your opinion count.</small>
              </div>
            <div class="like_up_btn"><a href="">SIGN IN</a></div>
          </div>
 -->


        <span id="count_dislike_<?php echo  $store_id; ?>"  >
        <?php

            //echo $store_id.'hawwwwwwww<br/>'; die;
            $servername = "localhost";
            $username = "root";
            $password = "sunny007";
            $db = "couponzipcode";
            //$store_id = $_GET['id'];
            //$user_id =$_GET['user_id'];
            //echo $store_id; die;
            // Create connection
            $conn = new mysqli($servername, $username, $password, $db);
            // Check connection




            $sql = "SELECT * FROM like_dislike WHERE store_id='$store_id' AND status='0'";
            //echo $sql; die;
            $result =$conn->query($sql);
            $count = $result->num_rows;
            if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                 $count1  = $count;
            }
            echo $count1; 
            //die;
            } 
            else 
            {
                echo '0';
            } 
        ?>
        </span>

    </li>
        </ul>
        
    </div>
                                        



<?php }?>


<!--Witout log in -->




                                                                             
                                    <div class="st_add"><span><?php echo $stores['stores_address']; ?></span><br>
                                        <span><?php echo $stores['stores_city']; ?></span><br>
                                        <span> <?php echo $stores['stores_state'] . ' ' . $stores['stores_zip']; ?></span><br>
                                        <span><?php echo '<a href="tel:' . $stores['stores_phone'] . '" class="phone_no">' . $stores['stores_phone'] . '</a>'; ?></span><br>
                                        <?php echo $stores['store_type'] ?>
                                        <div class="store_type_cust">American(New), Wine Bars</div>
                                    </div>
                                    <div class="st_para"><p></p></div>
                                </div>

                                <div class="radio_for_dsktop">
                                    <div class="st_off">
                                        <div class="st_discription_wrap">
                                            <?php
                                            $j = 0;
                                            $class = '';
                                            $coupons = '';
                                            $ex = 0;
                                            foreach ($new_data as $data)
                                            {
                                                $ex++;
                                                $j++; $k++;
                                            ?>
                                                <div class="st_discription <?php echo $i ?>" id="<?php echo $i ?>">
                                                <?php
                                                    $checked = '';
                                                    if($j == 1)
                                                    {
                                                        $checked = 'checked = "checked"';
                                                        $cpn = base64_encode($data['localcoupons_id']);
                                                    }

                                                    $class = $class . ' ' . $i;
                                                    $coupontype = 'LC';
                                                    $coupons .= '<div class="input_wrap"><input onclick="toggle_desc(this);" class="localcoupon" data-desc="iclasss_'.$ex.'" hide_data="' . $store_id . '" name="localcoupon' . $store_id . '" rel="dispdesc_' . $i . '" data_id="' . $i . '" coupontype="' . $coupontype . '" type="radio" value="' . base64_encode($data['localcoupons_id']) . '" '.$checked.' /><label>' . $data['localcoupons_name'] . '</label></div><br />';

                                                    if ($j)
                                                    {
                                                        ?>
                                                        </div>  
                                                        <?php
                                                    }

                                                    $i++;
                                            }
                                            ?>
                                        </div>
                                        <div class="off_check"><br><?php echo $coupons;?></div>
                                    </div>
                                </div>
                                <div class="clearfix"></div>
                                <?php
                                //Miles away
                                if (!empty($ids_distance))
                                {
                                    $ids_distance[$store_id] = number_format($ids_distance[$store_id], 2, '.', '');
                                    echo '<div class="miles_away">' . $ids_distance[$store_id] . ' miles away </div>';
                                }
                                ?>

                                
                            </div>
<?php $abc = $i;?>
<div class="toggle_inner"><a id="tog_inn_<?php echo $i; ?>" class="tooglepkin <?=  "tog_inn"; ?>" data-id = "<?= $i; ?>" href="javascript:void(0);"><i class="fa fa-angle-down"></i>Show More</a></div>

<div class="cust_bot_wrap_<?php echo $i; ?> cust_bot_wrap_extra" >
                                <div class="mob_tab_radio">
                                    <div class="st_off">
                                        <div class="off_check">
                                            <br>
                                            <?php
                                            echo $coupons;
                                            ?>
                                        </div>
                                        
                                    </div>
                                </div>
                                <div class="st_discription_wrap">
                                    <?php
                                    $j = 0;
                                    $class = '';
                                    $coupons = '';
                                    $ex = 0;
                                    foreach ($new_data as $data)
                                    {
                                        $ex++;
                                        // print_r($data);die;
                                        $j++; $k++;
                                        ?>
                                        <div class="st_discription <?php echo $i ?>" id="<?php echo $i ?>">
                                            <?php
                                            ?>                        
                                            <div id="dispdesc_<?php echo $i; ?>" <?php if($j == 1){ ?> style="display:none;" <?php }else{ ?> style="display:none;" <?php } ?> class="couponmouseover dispdesc <?php echo $store_id; ?>">
                                                <div class="dispdesc_inner iclasss_<?php echo $ex; ?>">
                                                    <?php
                                                    $desc = $data['localcoupons_desc'];
                                                    $desc_length = strlen($desc);
                                                    if ($desc_length >= 80)
                                                    {
                                                        $desc = substr($desc, 0, 70);
                                                        $desc = substr($desc, 0, strripos($desc, ' '));
                                                        echo $desc . '...';
                                                    }
                                                    else
                                                    {
                                                        echo $desc;
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                            <?php
                                            $checked = ($j == 1)? 'checked':'';
                                            if($j == 1)
                                            {
                                                $cpn =base64_encode($data['localcoupons_id']);
                                            }

                                            $class = $class . ' ' . $i;
                                            $coupontype = 'LC';
                                            $coupons .= '<div class="input_wrap"><input onclick="toggle_desc(this);" class="localcoupon" data-desc="iclasss_'.$ex.'" hide_data="' . $store_id . '" name="localcoupon' . $store_id . '" rel="dispdesc_' . $i . '" data_id="' . $i . '" coupontype="' . $coupontype . '" type="radio" value="' . base64_encode($data['localcoupons_id']) . '" '.$checked.' /><label>' . $data['localcoupons_name'] . '</label></div><br />';
                                            if ($j)
                                            {
                                            ?>
                                                </div>
                                            <?php
                                            }

                                            $i++;
                                    }
                                    ?>
                                </div>

                                <div id="dispdesc_<?php echo $i; ?>" <?php if($j == 1){ ?> style="display:none;" <?php }else{ ?> style="display:block;" <?php } ?> class="couponmouseover dispdesc <?php echo $store_id; ?>">
                                    <div class="dispdesc_inner">
                                        <?php
                                        $desc = $data['localcoupons_desc'];
                                        $desc_length = strlen($desc);
                                        if ($desc_length >= 80) {
                                            $desc = substr($desc, 0, 70);
                                            $desc = substr($desc, 0, strripos($desc, ' '));
                                            echo $desc . '...';
                                        } 
                                        else 
                                        {
                                            echo $desc;
                                        }
                                        ?>
                                    </div>
                                </div>

          
          <div class="go_btn <?php echo $class ?>" id="dispdesc_<?php echo $i; ?>">
              <a href="coupon.php?coupontype=<?php echo $coupontype; ?>&coupon_id=<?php echo $cpn; ?>">Go</a>
          </div>


<div class="toggle_inner"><a id="tog_inn_up_<?php echo $abc; ?>" class="<?=  "tog_inn_up"; ?>" data-id = "<?= $abc; ?>" href="javascript:void(0);"><i class="fa fa-angle-up"></i>Show less</a></div>


</div><!-- cust_bot_wrap closed -->







                                    <div class="stor_bottom" rel="email_<?php echo $i; ?>">
                                       <!-- <div class="fb-share-button" data-href="https://developers.facebook.com/docs/plugins/" data-layout="button_count" data-size="small" data-mobile-iframe="true"><a class="fb-xfbml-parse-ignore" target="_blank" href="https://www.facebook.com/sharer/sharer.php?u=https%3A%2F%2Fdevelopers.facebook.com%2Fdocs%2Fplugins%2F&amp;src=sdkpreparse">Share</a></div>-->
                                        <!-- Go to www.addthis.com/dashboard to customize your tools -->
                                        <div class="addthis_sharing_toolbox social" data-url='http://couponzipcode.com/store_details.php?storefromfeeds_id=<?php echo base64_encode($storefromfeeds_id); ?>st_id=<?php echo base64_encode($store_id); ?>' data-title='<?php echo $stores['stores_name']; ?>' data-picture='abc.jpg' data-description='<?php echo $stores['stores_name']; ?>'></div>
                                        <div class="email_share new_email_link" cpn="<?php echo $cpn; ?>" id="<?php echo $store_id; ?>" data="http://couponzipcode.com/store_details.php?storefromfeeds_id=<?php echo base64_encode($storefromfeeds_id); ?>&st_id=<?php
                         echo base64_encode($store_id);
                         if (!empty($ids_distance)) {
                             echo '&miles=' . $ids_distance[$store_id];
                         }
                         ?>"><img height="32" width="32" src="images/mail.png"/></div>

                                        <div class="map_it"><a href="/map_it.php?map_it=<?php echo base64_encode($store_id); ?>"><img src="images/map_it.png" target="" ></a></div>
                                        <div class="st_para2">
                                            <p></p>
                                        </div>
                                    </div>




                                </div>
                                <div class="st_bottom">
                                    <img src="images/st_bottom.gif">
                                </div>
                            </div>
                        </div>  
                    </div>
                <?php }
            }
            ?>
            <?php
            $i++;
            $flag++;
        }
        ?>
    <?php }}
    ?>
    <?php
}}
if ($err_msg == true) {
    echo $no_coupon_message;
}
?>
<input type="hidden" value="<?php echo $_SESSION['loggedMemberID']; ?>" id="user_id" >

<script>

    $(document).on("click", '.tog_inn',function(){
        //$('.tog_inn').one('click', function() {
        //var data_id = $('.tog_inn').attr('data-id').val();
        var data_id = $(this).data('id');
        //alert(data_id);
        $('#tog_inn_'+ data_id).css("display","none");
        $('.cust_bot_wrap_' + data_id).show();
    });



    $(document).on("click", '.tog_inn_up',function(){
        var data_id = $(this).data('id');
        //alert(data_id);
        $('#tog_inn_'+ data_id).css("display","block");
        $('.cust_bot_wrap_' + data_id).hide();
    });

    //var note = ($('.addthis_sharing_toolbox ').data('note'));
    //console.log(note)
    //var addthis_config = addthis_config||{};
    //addthis_config.ui_email_from = 'deverma245@gmail.com';
    //addthis_config.ui_use_css = false;
    //addthis_config.ui_delay = 1000;
    //var addthis_share = {note: note,email_vars: { price: "30$", var_distance: "10" } };

    (function ($) {

    $(document).ready(function () {
    var group = '';
    var coupontype = '';
    var coupon_id = '';
    var link = '';
    /*Inputs Discriptions*/
    $(".localcoupon").each(function ()
    {
        $(this).click(function ()
        {
            // var datahide = $(this).attr('hide_data'); //699
            // alert(datahide);

            //   // $('.' + datahide).css('display', 'none');

            // var relation = $(this).attr('rel'); //dispdesc_1
            // var dataid = $(this).attr('data_id'); //1

            // //   $('#' + relation).css('display', 'block');

            // coupon_id = $(this).val(); //MTY2MA==
            // $('#'+datahide).attr('cpn',coupon_id) //MTY2MA==
            // coupontype = $(this).attr('coupontype'); //LC
            // link = 'coupon.php?coupontype=' + coupontype + '&coupon_id=' + coupon_id;
            
            // $('#'+relation+' a').attr('href', link);
            
            // $('.' + dataid + ' a').attr('href', link);
            
            // $("." + relation).each(function () {
            //     $(this).attr('href', link);
            // });
              //   /*$('.dispdesc').each(function(){
              //    $(this).hide();
              //    });*/
              //   $(this).closest('.st_mid_top').find("'.st_mid_top_left #" + relation + "'").show();
              //   $(this).closest('.st_mid_top').find("'.st_mid_top_left #" + relation + "'").siblings().hide();
        });
    });

    $('.go_btn a').click(function (event) {
        // event.preventDefault();
            // comment by ashok 11-07-2017
         //var id = $(this).parent().attr('id');
         //var href = $(this).attr('href');
         //$(this).attr('href',href+'&id='+id);  
   });
    /*$('.go_btn a').each(function(){
     $(this).click(function(){
     $(this).parent().siblings('.st_off').find('.localcoupon').each(function(){
     if($(this).is(':checked')){
     selected_coupon = $(this).val();
     }else{
     selected_coupon = 'hi';
     }
     });
     console.debug(selected_coupon);
     });
     return false;
     });*/


// commented by deverma
    /*    $('.go_btn a').each(function(){
     $(this).click(function(){
     coupon_id = '';
     coupontype = '';
     group = $(this).attr('group');
     
     coupon_id = $('input[name="localcoupon'+group+'"]:checked').val();
     coupontype = $('input[name="localcoupon'+group+'"]:checked').attr('coupontype');
     
     if(coupon_id != null){
     link = '/coupon.php?coupontype='+coupontype+'&coupon_id='+coupon_id;
     $(this).attr('href',link);
     return true;
     }else{
     console.debug('error');
     }
     return false;
     });
     });*/

    });
    })(jQuery);

    $(document).on('click', '.at-icon-email', function ()
    {
        var id = $(this).parent().parent().parent().parent().parent().attr('rel');
        var image = $('#' + id).attr('src');
    });

    var addthis_config = addthis_config || {};
    addthis_config.ui_email_from = 'Coupons@CouponZipcode.com';
    var addthis_share = {email_vars: {address: image}};
    
    // Alert a message when the user shares somewhere
    function shareEventHandler(evt)
    {
        if (evt.type == 'addthis.menu.share')
        {
            evt.data.share.url = "http://www.sourcen.com";
            addthis.update('share', 'url', 'http://www.sourcen.com');
            str = JSON.stringify(evt.data);
        }
    }

    // Listen for the share event
    addthis.addEventListener('addthis.menu.share', shareEventHandler);

    function like_stores(id)
    {
        $.ajax({
            data:{id:id, user_id:$('#user_id').val()},
            type: "get",
            url: "like_bk.php",
            success: function(data)
            {
               // $('#count_like_' + id).html(data);
                var obj = JSON.parse(data)
                 $('#count_like_' + id).html(obj.like_count);
                $('#count_dislike_' + id).html(obj.dislike_count);
               

            }
        });
    }

    function dislike_stores(id)
    {
        $.ajax({
            data:{id:id, user_id:$('#user_id').val()},
            type: "get",
            url: "dis_like.php",
            success: function(data)
            {
              //  $('#count_dislike_' + id).html(data);
                var obj = JSON.parse(data)
                $('#count_dislike_' + id).html(obj.dislike_count);
                $('#count_like_' + id).html(obj.like_count);
                //var  likes=$('#count_like_' + id).html();

                //var count_data =likes- 1;
               // $('#count_like_' + id).html(count_data)

               
            }
        });
    }

 // $(document).on('click','#tog_inn',function(){
 //              $('.cust_bot_wrap').slideToggle();

 //            });






$(".like-button-view").click(function(){
      $('.QTPopup_firstsignup').css("display","block");
        // $('.like_up').slideToggle();
         
    });
$(".like-button-view-close").click(function(){
      $('.QTPopup_firstsignup').css("display","none");
        // $('.like_up').slideToggle();
         
    });
    


//     $(document).on("click", '.thumbs_down',function(){
//         $('.like_up').css("display","none");
//           $('.like_down').slideToggle();
//            });
 
// });


//   $(document).ready(function () {

//     if ( $(window).width() < 1024) {

//       alert('hello');
//         $(".cust_bot_wrap").css('display','none');
//     }
//     else {
//     alert('helldo');
//         $(".cust_bot_wrap").css('display','block');
//     }

// });


</script>
<script type="text/javascript">
    function toggle_desc(ele)
    {
        var cls = $(ele).attr('data-desc');
        var parent = $(ele).parents('div.pro_row');

        parent.find('.dispdesc_inner').css('display', 'none');
        parent.find('.couponmouseover').css('display', 'none');
        
        parent.find("div." + cls).css('display', 'block');
        parent.find("div." + cls).parents('.couponmouseover').css('display', 'block');

        var coupon_id = $(ele).val();
        var coupontype = $(ele).attr('coupontype');
        var link = 'coupon.php?coupontype=' + coupontype + '&coupon_id=' + coupon_id;
        parent.find(".go_btn a").attr("href", link);
    }

    $(document).ready(function () {
        $(".pro_row").each(function (i,e) {
            // if ($(e).find('.radio_for_dsktop').find('.input_wrap').children("[data-desc='iclasss_1']") == '')
            // {
                $(e).find('.radio_for_dsktop').find('.input_wrap').children("[data-desc='iclasss_1']").attr('checked', 'checked');
            // }
        })
    });
</script>
