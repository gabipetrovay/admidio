<?php
/******************************************************************************
 * Admidio update check
 *
 * Copyright    : (c) 2004 - 2015 The Admidio Team
 * Homepage     : http://www.admidio.org
 * License      : GNU Public License 2 http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Parameters:
 *
 * mode         : 1 - (Default) check availability of updates
 *                2 - Show results of updatecheck
 *
 *****************************************************************************/

require_once('../../system/common.php');
require_once('../../system/login_valid.php');

// Initialize and check the parameters
$getMode = admFuncVariableIsValid($_GET, 'mode', 'numeric', array('defaultValue' => 1, 'directOutput' => true));

if($getMode == 3 && !$gCurrentUser->isWebmaster())
{
    echo $gL10n->get('SYS_NO_RIGHTS');
    exit();
}

// Funktion zur Ermittlung der Update-Version
function GetUpdateVersion($update_info, $search)
{
    // Variablen festlegen
    $i = 0;
    $pointer = '';
    $update_version = '';
    $current_version_start = strpos($update_info, $search);
    $adding = strlen($search)-1;

    // Version auslesen
    while($pointer !== '\n')
    {
        $i++;
        $update_version .= $pointer;
        $pointer = substr($update_info, $current_version_start+$adding+$i, 1);
    }

    return trim($update_version, "\n\r");
}

// Funktion zur Überprüfung eines Updates
function CheckVersion($current_version, $check_stable_version, $check_beta_version, $beta_release, $beta_flag)
{
    // Updatezustand (0 = Kein Update, 1 = Neue stabile Version, 2 = Neue Beta-Version, 3 = Neue stabile + Beta Version)
    $update = 0;

    // Zunächst auf stabile Version prüfen
    $statusStable = version_compare($check_stable_version, $current_version);
    if($statusStable === 1)
    {
        $update = 1;
    }

    // Jetzt auf Beta Version prüfen
    $statusBeta = version_compare($check_beta_version, $current_version);
    if($statusBeta === 1 || ($statusBeta === 0 && version_compare($beta_release, $beta_flag) === 1))
    {
        if($update === 1)
        {
            $update = 3;
        }
        else
        {
            $update = 2;
        }
    }

    return $update;
}

// Erreichbarkeit der Updateinformation prüfen und bei Verbindung
// verfügbare Admidio Versionen vom Server einlesen (Textfile)
// Zunächst die Methode selektieren (CURL bevorzugt)
if(@file_get_contents('http://www.admidio.org/update.txt') === false)
{
    // Admidio Versionen nicht auslesbar
    $stable_version = 'n/a';
    $beta_version = 'n/a';
    $beta_release = '';

    $version_update = 99;
}
else
{
    $update_info = file_get_contents('http://www.admidio.org/update.txt');

    // Admidio Versionen vom Server übergeben
    $stable_version = GetUpdateVersion($update_info, 'Version=');
    $beta_version   = GetUpdateVersion($update_info, 'Beta-Version=');
    $beta_release   = GetUpdateVersion($update_info, 'Beta-Release=');

    // Keine Stabile Version verfügbar (eigentlich unmöglich)
    if($stable_version === '')
    {
        $stable_version = 'n/a';
    }

    // Keine Beatversion verfügbar
    if($beta_version === '')
    {
        $beta_version = 'n/a';
        $beta_release = '';
    }

    // Auf Update prüfen
    $version_update = CheckVersion(ADMIDIO_VERSION, $stable_version, $beta_version, $beta_release, (string)ADMIDIO_VERSION_BETA);
}


 // Nur im Anzeigemodus geht es weiter, ansonsten kann der aktuelle Updatestand
// in der Variable $version_update abgefragt werden.
// $version_update (0 = Kein Update, 1 = Neue stabile Version, 2 = Neue Beta-Version, 3 = Neue stabile + Beta Version, 99 = Keine Verbindung)

if($getMode == 2)
{
    /***********************************************************************/
    /* Updateergebnis anzeigen */
    /***********************************************************************/

    if($version_update === 1)
    {
        $versionstext = $gL10n->get('UPD_NEW');
    }
    elseif($version_update === 2)
    {
        $versionstext = $gL10n->get('UPD_NEW_BETA');
    }
    elseif($version_update === 3)
    {
        $versionstext = $gL10n->get('UPD_NEW_BOTH');
    }
    elseif($version_update === 99)
    {
        $admidio_link = '<a href="http://www.admidio.org/index.php?page=download"" target="_blank">Admidio</a>';
        $versionstext = $gL10n->get('UPD_CONNECTION_ERROR', $admidio_link);
    }
    else
    {
        if(ADMIDIO_VERSION_BETA > 0)
        {
            $versionstext_beta = 'Beta ';
        }
        else
        {
            $versionstext_beta = ' ';
        }
        $versionstext = $gL10n->get('UPD_NO_NEW', $versionstext_beta);
    }

    echo'
    <p>'.$gL10n->get('UPD_CURRENT_VERSION').':&nbsp;'.ADMIDIO_VERSION_TEXT.'</p>
    <p>'.$gL10n->get('UPD_STABLE_VERSION').':&nbsp;
        <a class="btn" href="http://www.admidio.org/index.php?page=download" target="_blank"><img
            src="'.THEME_PATH.'/icons/update_link.png" alt="'.$gL10n->get('UPD_ADMIDIO').'">'.$stable_version. '</a>
        <br />
        '.$gL10n->get('UPD_BETA_VERSION').': &nbsp;';

            if($version_update !== 99 && $beta_version !== 'n/a')
            {
                echo '
                <a class="btn" href="http://www.admidio.org/index.php?page=download" target="_blank"><img
                    src="'.THEME_PATH.'/icons/update_link.png" alt="'.$gL10n->get('UPD_ADMIDIO').'">'.$beta_version.'&nbsp;Beta&nbsp;'.$beta_release.'</a>';
            }
            else
            {
                echo $beta_version;
            }
    echo '</p>
    <strong>'.$versionstext.'</strong>';
}

?>