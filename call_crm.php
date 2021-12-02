<?php

// Author: Alexander Bachschmid
// https://centralstationcrm.de/blog/2011-10-13-CentralStationCRM-bekommt-eine-API-fuer-externe-Programme-und-Tools
// https://42he.com/de/developer/crm

include_once ('includes/api.php');
include_once ('includes/config.php');

$client = new ApiClient(API_URL, USERNAME, PASSWORD);

// First, Call the Shop-Users
callCustomersForCrm($client);

// After that, Call the Orders
callOrdersForCrm($client);

function callCustomersForCrm ($client) {
  if($aCustomers = $client->get('customers')['data'])
  {
    foreach ($aCustomers as $i => $aCustomer)
    {
      // Try to Find the Person from CRM and also Decode the Array
      $aPerson = apiCrm('GET', 'people/search.json', [
        "email" => $aCustomer['email'],
      ])[0]['person'];

      // Get Customer with Address
      $aCustomer = $client->get('customers/'.$aCustomer['id'])['data'];
      $aAddressBilling  = $aCustomer['defaultBillingAddress'];
      $aAddressShipping = $aCustomer['defaultShippingAddress'];
      $sSalut = createSalutation($aCustomer['salutation']);

      // Build Data
      $data = [
        "person" => [
          "salutation" => $sSalut,
          "title" => $aCustomer['title'],
          "first_name" => $aCustomer['firstname'],
          "name" => $aCustomer['lastname'],
          "tags_attributes" => [
            [
              "id" => "",
              "name" => "shop_konto"
            ]
          ],
          "tels_attributes" => [
            [
              "id" => "",
              "name" => $aAddressBilling['phone'] ?? $aAddressShipping['phone']
            ]
          ],
          "addrs_attributes" => [
            [
              "id" => "",
              "street" => $aAddressBilling['street'] ?? $aAddressShipping['street'],
              "zip" => $aAddressBilling['zipcode'] ?? $aAddressShipping['zipcode'],
              "city" => $aAddressBilling['city'] .'jo' ?? $aAddressShipping['city'],
              "country" => $aAddressBilling['country']['name'] ?? $aAddressShipping['country']['name'],
            ]
          ],
        ]
      ];

      // If Person and it's ID was Found
      if($aPerson && $aPerson['id']) {
        // Update Person
        $data['person']['id'] = $aPerson['id'];
        apiCrm('PUT', 'people/'.$aPerson['id'].'.json', $data);

      } else {
        // Create a new one
        $data['person']['emails_attributes'] = [
          "0" => [
            "id"=>"",
            "name"=>$aCustomer['email']
            // "atype"=>"office"
          ]
        ];
        apiCrm('POST', 'people.json', $data);
      }

      // End of Customer
    }
    // End of Customers
  }
}

function createSalutation ($sal) {
  switch (strtolower($sal)) {
    case 'mr':
      return 'Herr';

    case 'ms':
    case 'mrs':
      return 'Frau';
  }
  return '';
}

function apiCrm ($sMethod, $sUrl, $aSearch = []) {
  // API Curl Call
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, CRM_API_URL.$sUrl);
  curl_setopt($ch, CURLOPT_HEADER, true);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  // curl_setopt($ch, CURLOPT_NOBODY,1);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $sMethod);
  curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(
    array_merge(
      $aSearch,
      ['apikey' => CRM_API_KEY]
    )
  ));

  // Return headers seperatly from the Response Body
  $response = curl_exec($ch);
  $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
  $headers = substr($response, 0, $header_size);
  $body = substr($response, $header_size);

  // Close Connection
  curl_close($ch);

  // Return Body as Array
  return json_decode($body, true);
}

function callOrdersForCrm ($client) {

}
