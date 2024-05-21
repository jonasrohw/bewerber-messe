<?php
// Include PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader
require 'vendor/autoload.php';

// Define the path to the submissions count file
$counterFile = 'submission_count.txt';

// Check if the file exists, if not create it with an initial count of 0
if (!file_exists($counterFile)) {
    file_put_contents($counterFile, 0);
}

// Read the current count
$currentCount = (int) file_get_contents($counterFile);

// Increment the count for the new submission
$newCount = $currentCount + 1;

// Write the new count back to the file
file_put_contents($counterFile, $newCount);

// Generate the unique ID for the application
$unique_id = $newCount;

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $vorname = htmlspecialchars($_POST['vorname']);
    $nachname = htmlspecialchars($_POST['nachname']);
    $rufnummer = htmlspecialchars($_POST['rufnummer']);
    $email = htmlspecialchars($_POST['email']);
    $wohnanschrift = htmlspecialchars($_POST['wohnanschrift']);
    $plz = htmlspecialchars($_POST['plz']);
    $stadt = htmlspecialchars($_POST['stadt']);
    $fuehrerschein = htmlspecialchars($_POST['fuehrerschein']);
    $aufenthaltstitel = htmlspecialchars($_POST['aufenthaltstitel']);
    $stelle = htmlspecialchars($_POST['stelle']);
    $arbeitszeitmodell = htmlspecialchars($_POST['arbeitszeitmodell']);
    $samstags = htmlspecialchars($_POST['samstags']);
    $bewerbungStelle = htmlspecialchars($_POST['bewerbungStelle']);
    $ausbildungsplatz = htmlspecialchars($_POST['ausbildungsplatz']);
    $gehalt = htmlspecialchars($_POST['gehalt']);
    $startdatum = htmlspecialchars($_POST['startdatum']);
    $probe = htmlspecialchars($_POST['probe']);
    $ausbildung = htmlspecialchars($_POST['ausbildung']);
    $erfahrung = htmlspecialchars($_POST['erfahrung']);
    $datum = htmlspecialchars($_POST['datum']);

    // Work history
    $work_history = [];
    if (isset($_POST['unternehmen'])) {
        foreach ($_POST['unternehmen'] as $index => $unternehmen) {
            $position = htmlspecialchars($_POST['position'][$index]);
            $von = htmlspecialchars($_POST['von'][$index]);
            $bis = htmlspecialchars($_POST['bis'][$index]);
            $work_history[] = [
                'unternehmen' => $unternehmen,
                'position' => $position,
                'von' => $von,
                'bis' => $bis
            ];
        }
    }

    // File uploads (only saving file names in this example)
    $lebenslauf = $_FILES['lebenslauf']['name'];
    $zeugnisse = $_FILES['zeugnisse']['name'];

    // Save to .txt file
    $file_content = "Unique ID: $unique_id\nVorname: $vorname\nNachname: $nachname\nRufnummer: $rufnummer\nEmail: $email\nWohnanschrift: $wohnanschrift\nPLZ: $plz\nStadt: $stadt\nFührerschein: $fuehrerschein\nAufenthaltstitel: $aufenthaltstitel\nStelle: $stelle\nArbeitszeitmodell: $arbeitszeitmodell\nSamstags: $samstags\nBewerbung Stelle: $bewerbungStelle\nAusbildungsplatz: $ausbildungsplatz\nGehalt: $gehalt\nStartdatum: $startdatum\nProbe: $probe\nAusbildung: $ausbildung\nErfahrung: $erfahrung\nDatum: $datum\n\nWork History:\n";

    foreach ($work_history as $entry) {
        $file_content .= "Unternehmen: " . $entry['unternehmen'] . "\nPosition: " . $entry['position'] . "\nVon: " . $entry['von'] . "\nBis: " . $entry['bis'] . "\n\n";
    }

    $file_content .= "Lebenslauf: $lebenslauf\nZeugnisse: $zeugnisse\n";

    $file_path = 'applications/' . $unique_id . '.txt';
    file_put_contents($file_path, $file_content);

    // Handle the signature
    if (!empty($_POST['signature-data'])) {
        echo "true";
        $signatureData = $_POST['signature-data'];
        $signatureData = str_replace('data:image/png;base64,', '', $signatureData);
        $signatureData = str_replace(' ', '+', $signatureData);
        $signatureDecoded = base64_decode($signatureData);
        $signatureFile = 'applications/signature_' . $unique_id . '.png';
        file_put_contents($signatureFile, $signatureDecoded);
    }

    // Send email using PHPMailer
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->SMTPDebug = 0;                                       // Disable verbose debug output
        $mail->isSMTP();                                            // Send using SMTP
        $mail->Host       = 'localhost';                            // Set the SMTP server to send through
        $mail->SMTPAuth   = false;                                  // Disable SMTP authentication
        $mail->Port       = 1025;                                   // TCP port to connect to

        // Character encoding
        $mail->CharSet = 'UTF-8';

        // Recipients
        $mail->setFrom('your-email@example.com', 'Mailer');
        $mail->addAddress('your-email@example.com', 'Admin');       // Add a recipient
        // $mail->addReplyTo($email, "$vorname $nachname");

        // Attachments
        if (file_exists($_FILES['lebenslauf']['tmp_name']) && is_uploaded_file($_FILES['lebenslauf']['tmp_name'])) {
            $mail->addAttachment($_FILES['lebenslauf']['tmp_name'], $lebenslauf);
        }
        if (file_exists($_FILES['zeugnisse']['tmp_name']) && is_uploaded_file($_FILES['zeugnisse']['tmp_name'])) {
            $mail->addAttachment($_FILES['zeugnisse']['tmp_name'], $zeugnisse);
        }
        if (isset($signatureFile) && file_exists($signatureFile)) {
            $mail->addAttachment($signatureFile);
        }

        // Content
        $mail->isHTML(false);                                        // Set email format to plain text
        $mail->Subject = 'New Application Submission';
        $mail->Body    = $file_content;

        $mail->send();
        echo "<div style='padding: 20px; background-color: #f8f9fa; border: 1px solid #ced4da; border-radius: .25rem; text-align: center;'>
            <p>Vielen Dank für deine Bewerbung. Du bist im Lostopf. Deine Losnummer ist die <strong>$unique_id</strong></p>
            <p>Um xxUhr ist die Ziehung an unserem Stand. Viel Glück wünscht dir dein HM-Oberberg Team.</p>
        </div>";


    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}
?>
