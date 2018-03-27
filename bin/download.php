<?php

require __DIR__.'/../vendor/autoload.php';

use Carbon\Carbon;

// Configuration
$conf = include __DIR__ . '/../conf/config.php';

if(isset($argv[1])) {
	if(file_exists($argv[1])) {
		$conf = array_merge($conf, include $argv[1]);
	} else {
		die("Erreur : le fichier de configuration " . $argv[1] . " n'existe pas");
	}
} else {
	die("Erreur : configuration requise");
}


// Client + Requête
$fromDate = Carbon::now()->subDays($conf['period']['from']);
$toDate = Carbon::now()->subDays($conf['period']['to']);
$iraiser = new Pndata\iRaiser\Client($conf['api']['user'], $conf['api']['apiKey']);
$contactsFile = sprintf($conf['destination'] . '/' . $conf['contactsFileName'], $fromDate->format('Y.m.d'), $toDate->format('Y.m.d'));
$donationsFile = sprintf($conf['destination'] . '/' . $conf['donationsFileName'], $fromDate->format('Y.m.d'), $toDate->format('Y.m.d'));
$contacts = $iraiser->contacts()->withDonations()->fromDate($fromDate->format('Y-m-d'))->toDate($toDate->format('Y-m-d'));

// Téléchargement + Export
$page = 1;
$contactsData = [];
$donationsData = [];

function sendmail($fromDate, $toDate, $errorMsg=null) {
  global $conf;
  $status = $errorMsg ? "échoué : $errorMsg" : "réussi";
  $msg = "Téléchargement des données du {$fromDate->format('d/m/Y')} au {$toDate->format('d/m/Y')} $status";
  $transport = Swift_SmtpTransport::newInstance($conf['smtp']['host'], $conf['smtp']['port'])->setUsername($conf['smtp']['user'])->setPassword($conf['smtp']['password']);
  Swift_Mailer::newInstance($transport)->send(Swift_Message::newInstance()
    ->setSubject('[iRaiser '.$conf['account'].'] ' . ($errorMsg ? 'Erreur' : 'Succès'))
    ->setFrom(array($conf['notification']['from']))
    ->setTo($conf['notification']['to'])
    ->setBody($msg)
  );
}

function tocsv($line) {
  return implode(array_map(function($data) {
    return '"' . (is_null($data) ? '' : (is_bool($data) ? ($data ? 1 : 0) : $data)) . '"';
  }, $line), ';');
}

try {

  while(count($contactsTmp = $contacts->page($page)->get())) {
    $contactsTmp2 = [];
    $donationsTmp = [];
    foreach($contactsTmp as $contactRow) {
      $contactRow2 = [];
      foreach($conf['contactFields'] as $field) {
        $contactRow2[$field] = isset($contactRow[$field]) ? $contactRow[$field] : null;
      }
      $contactsTmp2[] = $contactRow2;

      if(isset($contactRow['donations'])) {
        foreach($contactRow['donations'] as $donationRow) {
          $donationRow2 = ['contactId' => $contactRow2['contactId']];
          foreach($conf['donationFields'] as $field) {
            $donationRow2[$field] = isset($donationRow[$field]) ? $donationRow[$field] : null;
          }
          $donationsTmp[] = $donationRow2;
        }
      }

    }
    $contactsData = array_merge($contactsData, $contactsTmp2);
    $donationsData = array_merge($donationsData, $donationsTmp);
    $page++;
  }

  $contactsCsv = [tocsv($conf['contactFields'])];
  $contactsCsv = array_merge($contactsCsv, array_map(function($row) {
    return tocsv(array_values($row));
  }, $contactsData));

  $donationsCsv = [tocsv(array_merge(['contactId'], $conf['donationFields']))];
  $donationsCsv = array_merge($donationsCsv, array_map(function($row) {
    return tocsv(array_values($row));
  }, $donationsData));
  $output = [implode($contactsCsv, PHP_EOL), implode($donationsCsv, PHP_EOL)];

  if(@file_put_contents($contactsFile, $output[0]) === false) {
    throw new Exception('Impossible d\'écrire dans le fichier ' . $contactsFile . ' : ' . error_get_last()['message']);
  }

  if(@file_put_contents($donationsFile, $output[1]) === false) {
    throw new Exception('Impossible d\'écrire dans le fichier ' . $donationsFile . ' : ' . error_get_last()['message']);
  }

  sendmail($fromDate, $toDate);

} catch(Exception $e) {
  sendmail($fromDate, $toDate, $e->getMessage());
}
