/* ####################################################

   Collecteur mail Tuleap

   F.BAGNOL fbagnol@tec6.fr

   31/12/2013

   #################################################### */

//////////////////////////////////////////////////////
/// Constantes
//////////////////////////////////////////////////////
$host         = 'http://forge.XXXXXXXx.XX';
$host_login   = $host .'/soap/?wsdl';
$host_tracker = $host .'/plugins/tracker/soap/?wsdl';
// SOAP options for debug
$soap_option  = array(
    'cache_wsdl' => WSDL_CACHE_NONE,
    'exceptions' => 1,
    'trace'      => 1
);


///////////////////////////////////////////////////////
/// Debut du main
///////////////////////////////////////////////////////

// On ouvre la boite mail
if (!($imap = imap_open("{mail.XXXXXX.XX:993/imap/ssl/novalidate-cert}", "EMAIL_ADRESS", "EMAIL_PASS"))) {
    throw new Exception('Impossible de se connecter au mail');
}

// On se connecte ? Tuleap
$client_login = new SoapClient($host_login, $soap_option);
$session_hash = $client_login->login('LOGIN_TULEAP', 'PASS_TULEAP')->session_hash;

$client_tracker = new SoapClient($host_tracker, $soap_option);

// Recherche des mails non lus
$mails = imap_search($imap, 'UNSEEN', SE_UID);

// On sort s'il n'y a pas de mails
if ($mails === false) {
    exit();
}

// pour chaque mail non lu
foreach ($mails as $mail) {

    $objHeader = imap_fetch_overview( $imap, $mail, FT_UID );

    // on r▒cup?re le sujet
    $sujet = decode_imap_text($objHeader[0]->subject);

    // on r▒cup?re le corps du mail
    $corps = imap_qprint(imap_fetchbody($imap, $mail , '1', FT_UID));

    // On cr▒e l'artefact
    $value = array(
    array(
        'field_name'  => 'summary',
        'field_label' => '',
        'field_value' => array(
            'value' => $sujet
         )
    ),
    array(
        'field_name'  => 'details',
        'field_label' => '',
        'field_value' => array(
            'value' => $corps
         )
    )
    );

    var_dump($value);

    $client_tracker->addArtifact($session_hash, 101, 10, $value);

}

// On ferme le mail
imap_close ( $imap);



function tracer($message) {
    @$fp = fopen("/var/log/collecteur_mail_tuleap.txt","a");
    @$date = strftime("%x %X");
    @fwrite($fp, "$date => $message\r\n");
    @fclose($fp);
}

function extract_attachments($connection, $message_number) {

    $attachments = array();
    $structure = imap_fetchstructure($connection, $message_number);

    if(isset($structure->parts) && count($structure->parts)) {

        for($i = 0; $i < count($structure->parts); $i++) {

            $attachments[$i] = array(
                'is_attachment' => false,
                'filename' => '',
                'name' => '',
                'attachment' => ''
            );

            if($structure->parts[$i]->ifdparameters) {
                foreach($structure->parts[$i]->dparameters as $object) {
                    if(strtolower($object->attribute) == 'filename') {
                        $attachments[$i]['is_attachment'] = true;
                        $attachments[$i]['filename'] = $object->value;
                    }
                }
            }

            if($structure->parts[$i]->ifparameters) {
                foreach($structure->parts[$i]->parameters as $object) {
                    if(strtolower($object->attribute) == 'name') {
                        $attachments[$i]['is_attachment'] = true;
                        $attachments[$i]['name'] = $object->value;
                    }
                }
            }

            if($attachments[$i]['is_attachment']) {
                $attachments[$i]['attachment'] = imap_fetchbody($connection, $message_number, $i+1);
                if($structure->parts[$i]->encoding == 3) { // 3 = BASE64
                    $attachments[$i]['attachment'] = base64_decode($attachments[$i]['attachment']);
                }
                elseif($structure->parts[$i]->encoding == 4) { // 4 = QUOTED-PRINTABLE
                    $attachments[$i]['attachment'] = quoted_printable_decode($attachments[$i]['attachment']);
                }
            }

        }

    }

    return $attachments;

}

#
# Decode UTF-8 and iso-8859-1 encoded text
#
function decode_imap_text($var)
{
        if(ereg("=\?.{0,}\?[Bb]\?",$var)) {
                $var = split("=\?.{0,}\?[Bb]\?",$var);

                while(list($key,$value)=each($var)){
                        if(ereg("\?=",$value)){
                                $arrTemp=split("\?=",$value);
                                $arrTemp[0]=base64_decode($arrTemp[0]);
                                $var[$key]=join("",$arrTemp);
                        }
                }
                $var=join("",$var);
        }

        if(ereg("=\?.{0,}\?Q\?",$var)) {
                $var = quoted_printable_decode($var);
                $var = ereg_replace("=\?.{0,}\?[Qq]\?","",$var);
                $var = ereg_replace("\?=","",$var);
        }

        return trim($var);
}

?>
