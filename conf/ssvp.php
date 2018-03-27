<?php

return [

    'account' => 'SSVP',

    'period' => [
        'from' => 7,
        'to'   => 1
    ],

    'api' => [
        'user' => 'ssvp_api',
        'apiKey' => '4Oz8oMmP'
    ],

    'destination' => 'C:\Users\matthieu.PNDATA\Projets\php\iraiser\iraiser-tocsv\dest',
    'contactsFileName' => 'contacts_%s_%s.txt',
    'donationsFileName' => 'donations_%s_%s.txt',

    'contactFields' => [
        'contactId', 'email', 'optinEmail', 'updateDate', 'country', 'civility', 'gender', 'lastName', 
        'firstName', 'company', 'birthdate', 'address1', 'address2', 'address3', 'address4',
        'zipcode', 'city', 'phone', 'mobile', 'phoneBusiness', 'optoutEmail', 'optoutMail', 'optinPhone', 'optoutPhone'
    ],

    'donationFields' => [
        'amount', 'canceled', 'date', 'donationId', 'fiscalreceiptRef', 
        'paymentServicesStatus', 'transactionNumber', 'type', 'reserved_affectation',
        'reserved_presdechezmoi', 'campaignOriginCode', 'reserved_code_media'
    ]

];