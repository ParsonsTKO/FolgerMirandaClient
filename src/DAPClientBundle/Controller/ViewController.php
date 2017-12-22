<?php
/**
 * File containing the ViewController class.
 *
 * (c) http://parsonstko.com/
 * (c) Developer jdiaz
 */

namespace DAPClientBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ViewController extends Controller
{
    /**
     * Redirect homepage.
     *
     * @param
     *
     * @return
     */
    public function homeAction(Request $request)
    {
        try {
            $searchService = $this->get('dap_client.service.content');
            $metadata = $searchService->getMetadata();

            $msg = $request->query->get('msg');
            $userMessage = '';
            switch($msg) {
                case 1:
                    $userMessage = 'We were not able to work out what you were hoping to search for.<br>If you would like, enter an asterisk (*) for a wildcard search.';
                    break;
            }

            $languagesOffered = $this->cheapLanguageDropDown(array("ben", "cze", "dan", "dut", "eng", "epo", "fre", "frm", "gaa", "ger", "grc", "ice", "ita", "lat", "mao", "mul", "pol", "por", "spa", "swa", "tlh", "und", "wel", "xho", "zxx", "zzx"));

            return $this->render(
                'DAPClientBundle::home.html.twig',
                array(
                    'metadata' => $metadata,
                    'usermessage' => $userMessage,
                    'languagesOffered' => $languagesOffered
                )
            );
        } catch (\Exception $e) {
            $this->get('dap_client.logger')->error($e->getMessage());
            throw $this->createNotFoundException('Page could not be found. Error: '.$e->getMessage());
        }
    }
    
    /**
     * Dowload images.
     *
     * @param
     *
     * @return
     */
    public function downloadImageAction($rootfile, $image)
    {
        try {
            $searchService = $this->get('dap_client.service.search');
            $viewSettings = $searchService->searchSettings['views']['detail'];
            $imagesEndPoint = $viewSettings['images_endpoint'];
            $imagesPath = $viewSettings['images_path'];
            $url = $imagesEndPoint . $imagesPath . $rootfile . '/' . $image;
            $headers = get_headers($url, 1);
            $contentType = $headers['Content-Type'];
            $contentLength = $headers['Content-Length'];
            
            if( stripos($headers[0],"200 OK") ){
                header("Pragma: public");
                header("Expires: 0");
                header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                header("Cache-Control: private",false);
                header("Content-Type: $contentType");
                header("Content-Disposition: attachment; filename=\"".basename($url)."\";" );
                header("Content-Transfer-Encoding: binary");
                header("Content-Length: ".$contentLength);
                ob_clean();
                flush();
                readfile( $url );
                
            } else {
                throw new \UnexpectedValueException("Image not found or empty");
            }
            
        } catch (\Exception $e) {
            $this->get('dap_client.logger')->error($e->getMessage());
            throw $this->createNotFoundException('Page could not be found. Error: '.$e->getMessage());
        }
    }

    /**
     * Dowload arbitrary binary files.
     *
     * @param
     *
     * @return
     */
    public function downloadBinaryAction($binaryFile)
    {
        try {
            $searchService = $this->get('dap_client.service.search');
            $viewSettings = $searchService->searchSettings['views']['detail'];
            $binaryEndPoint = $viewSettings['binary_endpoint'];
            $binaryPath = $viewSettings['binary_path'];
            $url = $binaryEndPoint . $binaryPath . '/' . $binaryFile;
            $headers = get_headers($url, 1);
            $contentType = $headers['Content-Type'];
            $contentLength = $headers['Content-Length'];

            if( stripos($headers[0],"200 OK") ){
                header("Pragma: public");
                header("Expires: 0");
                header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                header("Cache-Control: private",false);
                header("Content-Type: $contentType");
                header("Content-Disposition: attachment; filename=\"".basename($url)."\";" );
                header("Content-Transfer-Encoding: binary");
                header("Content-Length: ".$contentLength);
                ob_clean();
                flush();
                readfile( $url );

            } else {
                throw new \UnexpectedValueException("Image not found or empty");
            }

        } catch (\Exception $e) {
            $this->get('dap_client.logger')->error($e->getMessage());
            throw $this->createNotFoundException('Page could not be found. Error: '.$e->getMessage());
        }
    }


    public function cheapLanguageDropDown($langCodeArray)
    {
        $outvar = '';
        foreach ($langCodeArray as $code) {
            switch ($code) {
                case "aar":
                    $outvar .=  "<option value=\"aar\">Afar</option>";
                    break;
                case "abk":
                    $outvar .=  "<option value=\"abk\">Abkhazian</option>";
                    break;
                case "ace":
                    $outvar .=  "<option value=\"ace\">Achinese</option>";
                    break;
                case "ach":
                    $outvar .=  "<option value=\"ach\">Acoli</option>";
                    break;
                case "ada":
                    $outvar .=  "<option value=\"ada\">Adangme</option>";
                    break;
                case "ady":
                    $outvar .=  "<option value=\"ady\">Adyghe; Adygei</option>";
                    break;
                case "afa":
                    $outvar .=  "<option value=\"afa\">Afro-Asiatic languages</option>";
                    break;
                case "afh":
                    $outvar .=  "<option value=\"afh\">Afrihili</option>";
                    break;
                case "afr":
                    $outvar .=  "<option value=\"afr\">Afrikaans</option>";
                    break;
                case "ain":
                    $outvar .=  "<option value=\"ain\">Ainu</option>";
                    break;
                case "aka":
                    $outvar .=  "<option value=\"aka\">Akan</option>";
                    break;
                case "akk":
                    $outvar .=  "<option value=\"akk\">Akkadian</option>";
                    break;
                case "alb":
                    $outvar .=  "<option value=\"alb\">Albanian</option>";
                    break;
                case "alb":
                    $outvar .=  "<option value=\"alb\">Albanian</option>";
                    break;
                case "ale":
                    $outvar .=  "<option value=\"ale\">Aleut</option>";
                    break;
                case "alg":
                    $outvar .=  "<option value=\"alg\">Algonquian languages</option>";
                    break;
                case "alt":
                    $outvar .=  "<option value=\"alt\">Southern Altai</option>";
                    break;
                case "amh":
                    $outvar .=  "<option value=\"amh\">Amharic</option>";
                    break;
                case "ang":
                    $outvar .=  "<option value=\"ang\">English, Old (ca.450-1100)</option>";
                    break;
                case "anp":
                    $outvar .=  "<option value=\"anp\">Angika</option>";
                    break;
                case "apa":
                    $outvar .=  "<option value=\"apa\">Apache languages</option>";
                    break;
                case "ara":
                    $outvar .=  "<option value=\"ara\">Arabic</option>";
                    break;
                case "arc":
                    $outvar .=  "<option value=\"arc\">Official Aramaic (700-300 BCE); Imperial Aramaic (700-300 BCE)</option>";
                    break;
                case "arg":
                    $outvar .=  "<option value=\"arg\">Aragonese</option>";
                    break;
                case "arm":
                    $outvar .=  "<option value=\"arm\">Armenian</option>";
                    break;
                case "arn":
                    $outvar .=  "<option value=\"arn\">Mapudungun; Mapuche</option>";
                    break;
                case "arp":
                    $outvar .=  "<option value=\"arp\">Arapaho</option>";
                    break;
                case "art":
                    $outvar .=  "<option value=\"art\">Artificial languages</option>";
                    break;
                case "arw":
                    $outvar .=  "<option value=\"arw\">Arawak</option>";
                    break;
                case "asm":
                    $outvar .=  "<option value=\"asm\">Assamese</option>";
                    break;
                case "ast":
                    $outvar .=  "<option value=\"ast\">Asturian; Bable; Leonese; Asturleonese</option>";
                    break;
                case "ath":
                    $outvar .=  "<option value=\"ath\">Athapascan languages</option>";
                    break;
                case "aus":
                    $outvar .=  "<option value=\"aus\">Australian languages</option>";
                    break;
                case "ava":
                    $outvar .=  "<option value=\"ava\">Avaric</option>";
                    break;
                case "ave":
                    $outvar .=  "<option value=\"ave\">Avestan</option>";
                    break;
                case "awa":
                    $outvar .=  "<option value=\"awa\">Awadhi</option>";
                    break;
                case "aym":
                    $outvar .=  "<option value=\"aym\">Aymara</option>";
                    break;
                case "aze":
                    $outvar .=  "<option value=\"aze\">Azerbaijani</option>";
                    break;
                case "bad":
                    $outvar .=  "<option value=\"bad\">Banda languages</option>";
                    break;
                case "bai":
                    $outvar .=  "<option value=\"bai\">Bamileke languages</option>";
                    break;
                case "bak":
                    $outvar .=  "<option value=\"bak\">Bashkir</option>";
                    break;
                case "bal":
                    $outvar .=  "<option value=\"bal\">Baluchi</option>";
                    break;
                case "bam":
                    $outvar .=  "<option value=\"bam\">Bambara</option>";
                    break;
                case "ban":
                    $outvar .=  "<option value=\"ban\">Balinese</option>";
                    break;
                case "baq":
                    $outvar .=  "<option value=\"baq\">Basque</option>";
                    break;
                case "bas":
                    $outvar .=  "<option value=\"bas\">Basa</option>";
                    break;
                case "bat":
                    $outvar .=  "<option value=\"bat\">Baltic languages</option>";
                    break;
                case "bej":
                    $outvar .=  "<option value=\"bej\">Beja; Bedawiyet</option>";
                    break;
                case "bel":
                    $outvar .=  "<option value=\"bel\">Belarusian</option>";
                    break;
                case "bem":
                    $outvar .=  "<option value=\"bem\">Bemba</option>";
                    break;
                case "ben":
                    $outvar .=  "<option value=\"ben\">Bengali</option>";
                    break;
                case "ber":
                    $outvar .=  "<option value=\"ber\">Berber languages</option>";
                    break;
                case "bho":
                    $outvar .=  "<option value=\"bho\">Bhojpuri</option>";
                    break;
                case "bih":
                    $outvar .=  "<option value=\"bih\">Bihari languages</option>";
                    break;
                case "bik":
                    $outvar .=  "<option value=\"bik\">Bikol</option>";
                    break;
                case "bin":
                    $outvar .=  "<option value=\"bin\">Bini; Edo</option>";
                    break;
                case "bis":
                    $outvar .=  "<option value=\"bis\">Bislama</option>";
                    break;
                case "bla":
                    $outvar .=  "<option value=\"bla\">Siksika</option>";
                    break;
                case "bnt":
                    $outvar .=  "<option value=\"bnt\">Bantu languages</option>";
                    break;
                case "bos":
                    $outvar .=  "<option value=\"bos\">Bosnian</option>";
                    break;
                case "bra":
                    $outvar .=  "<option value=\"bra\">Braj</option>";
                    break;
                case "bre":
                    $outvar .=  "<option value=\"bre\">Breton</option>";
                    break;
                case "btk":
                    $outvar .=  "<option value=\"btk\">Batak languages</option>";
                    break;
                case "bua":
                    $outvar .=  "<option value=\"bua\">Buriat</option>";
                    break;
                case "bug":
                    $outvar .=  "<option value=\"bug\">Buginese</option>";
                    break;
                case "bul":
                    $outvar .=  "<option value=\"bul\">Bulgarian</option>";
                    break;
                case "bur":
                    $outvar .=  "<option value=\"bur\">Burmese</option>";
                    break;
                case "bur":
                    $outvar .=  "<option value=\"bur\">Burmese</option>";
                    break;
                case "byn":
                    $outvar .=  "<option value=\"byn\">Blin; Bilin</option>";
                    break;
                case "cad":
                    $outvar .=  "<option value=\"cad\">Caddo</option>";
                    break;
                case "cai":
                    $outvar .=  "<option value=\"cai\">Central American Indian languages</option>";
                    break;
                case "car":
                    $outvar .=  "<option value=\"car\">Galibi Carib</option>";
                    break;
                case "cat":
                    $outvar .=  "<option value=\"cat\">Catalan; Valencian</option>";
                    break;
                case "cau":
                    $outvar .=  "<option value=\"cau\">Caucasian languages</option>";
                    break;
                case "ceb":
                    $outvar .=  "<option value=\"ceb\">Cebuano</option>";
                    break;
                case "cel":
                    $outvar .=  "<option value=\"cel\">Celtic languages</option>";
                    break;
                case "cha":
                    $outvar .=  "<option value=\"cha\">Chamorro</option>";
                    break;
                case "chb":
                    $outvar .=  "<option value=\"chb\">Chibcha</option>";
                    break;
                case "che":
                    $outvar .=  "<option value=\"che\">Chechen</option>";
                    break;
                case "chg":
                    $outvar .=  "<option value=\"chg\">Chagatai</option>";
                    break;
                case "chi":
                    $outvar .=  "<option value=\"chi\">Chinese</option>";
                    break;
                case "chk":
                    $outvar .=  "<option value=\"chk\">Chuukese</option>";
                    break;
                case "chm":
                    $outvar .=  "<option value=\"chm\">Mari</option>";
                    break;
                case "chn":
                    $outvar .=  "<option value=\"chn\">Chinook jargon</option>";
                    break;
                case "cho":
                    $outvar .=  "<option value=\"cho\">Choctaw</option>";
                    break;
                case "chp":
                    $outvar .=  "<option value=\"chp\">Chipewyan; Dene Suline</option>";
                    break;
                case "chr":
                    $outvar .=  "<option value=\"chr\">Cherokee</option>";
                    break;
                case "chu":
                    $outvar .=  "<option value=\"chu\">Church Slavic; Old Slavonic; Church Slavonic; Old Bulgarian; Old Church Slavonic</option>";
                    break;
                case "chv":
                    $outvar .=  "<option value=\"chv\">Chuvash</option>";
                    break;
                case "chy":
                    $outvar .=  "<option value=\"chy\">Cheyenne</option>";
                    break;
                case "cmc":
                    $outvar .=  "<option value=\"cmc\">Chamic languages</option>";
                    break;
                case "cop":
                    $outvar .=  "<option value=\"cop\">Coptic</option>";
                    break;
                case "cor":
                    $outvar .=  "<option value=\"cor\">Cornish</option>";
                    break;
                case "cos":
                    $outvar .=  "<option value=\"cos\">Corsican</option>";
                    break;
                case "cpe":
                    $outvar .=  "<option value=\"cpe\">Creoles and pidgins, English based</option>";
                    break;
                case "cpf":
                    $outvar .=  "<option value=\"cpf\">Creoles and pidgins, French-based</option>";
                    break;
                case "cpp":
                    $outvar .=  "<option value=\"cpp\">Creoles and pidgins, Portuguese-based</option>";
                    break;
                case "cre":
                    $outvar .=  "<option value=\"cre\">Cree</option>";
                    break;
                case "crh":
                    $outvar .=  "<option value=\"crh\">Crimean Tatar; Crimean Turkish</option>";
                    break;
                case "crp":
                    $outvar .=  "<option value=\"crp\">Creoles and pidgins</option>";
                    break;
                case "csb":
                    $outvar .=  "<option value=\"csb\">Kashubian</option>";
                    break;
                case "cus":
                    $outvar .=  "<option value=\"cus\">Cushitic languages</option>";
                    break;
                case "cze":
                    $outvar .=  "<option value=\"cze\">Czech</option>";
                    break;
                case "dak":
                    $outvar .=  "<option value=\"dak\">Dakota</option>";
                    break;
                case "dan":
                    $outvar .=  "<option value=\"dan\">Danish</option>";
                    break;
                case "dar":
                    $outvar .=  "<option value=\"dar\">Dargwa</option>";
                    break;
                case "day":
                    $outvar .=  "<option value=\"day\">Land Dayak languages</option>";
                    break;
                case "del":
                    $outvar .=  "<option value=\"del\">Delaware</option>";
                    break;
                case "den":
                    $outvar .=  "<option value=\"den\">Slave (Athapascan)</option>";
                    break;
                case "dgr":
                    $outvar .=  "<option value=\"dgr\">Dogrib</option>";
                    break;
                case "din":
                    $outvar .=  "<option value=\"din\">Dinka</option>";
                    break;
                case "div":
                    $outvar .=  "<option value=\"div\">Divehi; Dhivehi; Maldivian</option>";
                    break;
                case "doi":
                    $outvar .=  "<option value=\"doi\">Dogri</option>";
                    break;
                case "dra":
                    $outvar .=  "<option value=\"dra\">Dravidian languages</option>";
                    break;
                case "dsb":
                    $outvar .=  "<option value=\"dsb\">Lower Sorbian</option>";
                    break;
                case "dua":
                    $outvar .=  "<option value=\"dua\">Duala</option>";
                    break;
                case "dum":
                    $outvar .=  "<option value=\"dum\">Dutch, Middle (ca.1050-1350)</option>";
                    break;
                case "dut":
                    $outvar .=  "<option value=\"dut\">Dutch; Flemish</option>";
                    break;
                case "dyu":
                    $outvar .=  "<option value=\"dyu\">Dyula</option>";
                    break;
                case "dzo":
                    $outvar .=  "<option value=\"dzo\">Dzongkha</option>";
                    break;
                case "efi":
                    $outvar .=  "<option value=\"efi\">Efik</option>";
                    break;
                case "egy":
                    $outvar .=  "<option value=\"egy\">Egyptian (Ancient)</option>";
                    break;
                case "eka":
                    $outvar .=  "<option value=\"eka\">Ekajuk</option>";
                    break;
                case "elx":
                    $outvar .=  "<option value=\"elx\">Elamite</option>";
                    break;
                case "eng":
                    $outvar .=  "<option value=\"eng\">English</option>";
                    break;
                case "enm":
                    $outvar .=  "<option value=\"enm\">English, Middle (1100-1500)</option>";
                    break;
                case "epo":
                    $outvar .=  "<option value=\"epo\">Esperanto</option>";
                    break;
                case "est":
                    $outvar .=  "<option value=\"est\">Estonian</option>";
                    break;
                case "ewe":
                    $outvar .=  "<option value=\"ewe\">Ewe</option>";
                    break;
                case "ewo":
                    $outvar .=  "<option value=\"ewo\">Ewondo</option>";
                    break;
                case "fan":
                    $outvar .=  "<option value=\"fan\">Fang</option>";
                    break;
                case "fao":
                    $outvar .=  "<option value=\"fao\">Faroese</option>";
                    break;
                case "fat":
                    $outvar .=  "<option value=\"fat\">Fanti</option>";
                    break;
                case "fij":
                    $outvar .=  "<option value=\"fij\">Fijian</option>";
                    break;
                case "fil":
                    $outvar .=  "<option value=\"fil\">Filipino; Pilipino</option>";
                    break;
                case "fin":
                    $outvar .=  "<option value=\"fin\">Finnish</option>";
                    break;
                case "fiu":
                    $outvar .=  "<option value=\"fiu\">Finno-Ugrian languages</option>";
                    break;
                case "fon":
                    $outvar .=  "<option value=\"fon\">Fon</option>";
                    break;
                case "fre":
                    $outvar .=  "<option value=\"fre\">French</option>";
                    break;
                case "frm":
                    $outvar .=  "<option value=\"frm\">French, Middle (ca.1400-1600)</option>";
                    break;
                case "fro":
                    $outvar .=  "<option value=\"fro\">French, Old (842-ca.1400)</option>";
                    break;
                case "frr":
                    $outvar .=  "<option value=\"frr\">Northern Frisian</option>";
                    break;
                case "frs":
                    $outvar .=  "<option value=\"frs\">Eastern Frisian</option>";
                    break;
                case "fry":
                    $outvar .=  "<option value=\"fry\">Western Frisian</option>";
                    break;
                case "ful":
                    $outvar .=  "<option value=\"ful\">Fulah</option>";
                    break;
                case "fur":
                    $outvar .=  "<option value=\"fur\">Friulian</option>";
                    break;
                case "gaa":
                    $outvar .=  "<option value=\"gaa\">Ga</option>";
                    break;
                case "gay":
                    $outvar .=  "<option value=\"gay\">Gayo</option>";
                    break;
                case "gba":
                    $outvar .=  "<option value=\"gba\">Gbaya</option>";
                    break;
                case "gem":
                    $outvar .=  "<option value=\"gem\">Germanic languages</option>";
                    break;
                case "geo":
                    $outvar .=  "<option value=\"geo\">Georgian</option>";
                    break;
                case "ger":
                    $outvar .=  "<option value=\"ger\">German</option>";
                    break;
                case "gez":
                    $outvar .=  "<option value=\"gez\">Geez</option>";
                    break;
                case "gil":
                    $outvar .=  "<option value=\"gil\">Gilbertese</option>";
                    break;
                case "gla":
                    $outvar .=  "<option value=\"gla\">Gaelic; Scottish Gaelic</option>";
                    break;
                case "gle":
                    $outvar .=  "<option value=\"gle\">Irish</option>";
                    break;
                case "glg":
                    $outvar .=  "<option value=\"glg\">Galician</option>";
                    break;
                case "glv":
                    $outvar .=  "<option value=\"glv\">Manx</option>";
                    break;
                case "gmh":
                    $outvar .=  "<option value=\"gmh\">German, Middle High (ca.1050-1500)</option>";
                    break;
                case "goh":
                    $outvar .=  "<option value=\"goh\">German, Old High (ca.750-1050)</option>";
                    break;
                case "gon":
                    $outvar .=  "<option value=\"gon\">Gondi</option>";
                    break;
                case "gor":
                    $outvar .=  "<option value=\"gor\">Gorontalo</option>";
                    break;
                case "got":
                    $outvar .=  "<option value=\"got\">Gothic</option>";
                    break;
                case "grb":
                    $outvar .=  "<option value=\"grb\">Grebo</option>";
                    break;
                case "grc":
                    $outvar .=  "<option value=\"grc\">Greek, Ancient (to 1453)</option>";
                    break;
                case "gre":
                    $outvar .=  "<option value=\"gre\">Greek, Modern (1453-)</option>";
                    break;
                case "grn":
                    $outvar .=  "<option value=\"grn\">Guarani</option>";
                    break;
                case "gsw":
                    $outvar .=  "<option value=\"gsw\">Swiss German; Alemannic; Alsatian</option>";
                    break;
                case "guj":
                    $outvar .=  "<option value=\"guj\">Gujarati</option>";
                    break;
                case "gwi":
                    $outvar .=  "<option value=\"gwi\">Gwich'in</option>";
                    break;
                case "hai":
                    $outvar .=  "<option value=\"hai\">Haida</option>";
                    break;
                case "hat":
                    $outvar .=  "<option value=\"hat\">Haitian; Haitian Creole</option>";
                    break;
                case "hau":
                    $outvar .=  "<option value=\"hau\">Hausa</option>";
                    break;
                case "haw":
                    $outvar .=  "<option value=\"haw\">Hawaiian</option>";
                    break;
                case "heb":
                    $outvar .=  "<option value=\"heb\">Hebrew</option>";
                    break;
                case "her":
                    $outvar .=  "<option value=\"her\">Herero</option>";
                    break;
                case "hil":
                    $outvar .=  "<option value=\"hil\">Hiligaynon</option>";
                    break;
                case "him":
                    $outvar .=  "<option value=\"him\">Himachali languages; Western Pahari languages</option>";
                    break;
                case "hin":
                    $outvar .=  "<option value=\"hin\">Hindi</option>";
                    break;
                case "hit":
                    $outvar .=  "<option value=\"hit\">Hittite</option>";
                    break;
                case "hmn":
                    $outvar .=  "<option value=\"hmn\">Hmong; Mong</option>";
                    break;
                case "hmo":
                    $outvar .=  "<option value=\"hmo\">Hiri Motu</option>";
                    break;
                case "hrv":
                    $outvar .=  "<option value=\"hrv\">Croatian</option>";
                    break;
                case "hsb":
                    $outvar .=  "<option value=\"hsb\">Upper Sorbian</option>";
                    break;
                case "hun":
                    $outvar .=  "<option value=\"hun\">Hungarian</option>";
                    break;
                case "hup":
                    $outvar .=  "<option value=\"hup\">Hupa</option>";
                    break;
                case "iba":
                    $outvar .=  "<option value=\"iba\">Iban</option>";
                    break;
                case "ibo":
                    $outvar .=  "<option value=\"ibo\">Igbo</option>";
                    break;
                case "ice":
                    $outvar .=  "<option value=\"ice\">Icelandic</option>";
                    break;
                case "ice":
                    $outvar .=  "<option value=\"ice\">Icelandic</option>";
                    break;
                case "ido":
                    $outvar .=  "<option value=\"ido\">Ido</option>";
                    break;
                case "iii":
                    $outvar .=  "<option value=\"iii\">Sichuan Yi; Nuosu</option>";
                    break;
                case "ijo":
                    $outvar .=  "<option value=\"ijo\">Ijo languages</option>";
                    break;
                case "iku":
                    $outvar .=  "<option value=\"iku\">Inuktitut</option>";
                    break;
                case "ile":
                    $outvar .=  "<option value=\"ile\">Interlingue; Occidental</option>";
                    break;
                case "ilo":
                    $outvar .=  "<option value=\"ilo\">Iloko</option>";
                    break;
                case "ina":
                    $outvar .=  "<option value=\"ina\">Interlingua (International Auxiliary Language Association)</option>";
                    break;
                case "inc":
                    $outvar .=  "<option value=\"inc\">Indic languages</option>";
                    break;
                case "ind":
                    $outvar .=  "<option value=\"ind\">Indonesian</option>";
                    break;
                case "ine":
                    $outvar .=  "<option value=\"ine\">Indo-European languages</option>";
                    break;
                case "inh":
                    $outvar .=  "<option value=\"inh\">Ingush</option>";
                    break;
                case "ipk":
                    $outvar .=  "<option value=\"ipk\">Inupiaq</option>";
                    break;
                case "ira":
                    $outvar .=  "<option value=\"ira\">Iranian languages</option>";
                    break;
                case "iro":
                    $outvar .=  "<option value=\"iro\">Iroquoian languages</option>";
                    break;
                case "ita":
                    $outvar .=  "<option value=\"ita\">Italian</option>";
                    break;
                case "jav":
                    $outvar .=  "<option value=\"jav\">Javanese</option>";
                    break;
                case "jbo":
                    $outvar .=  "<option value=\"jbo\">Lojban</option>";
                    break;
                case "jpn":
                    $outvar .=  "<option value=\"jpn\">Japanese</option>";
                    break;
                case "jpr":
                    $outvar .=  "<option value=\"jpr\">Judeo-Persian</option>";
                    break;
                case "jrb":
                    $outvar .=  "<option value=\"jrb\">Judeo-Arabic</option>";
                    break;
                case "kaa":
                    $outvar .=  "<option value=\"kaa\">Kara-Kalpak</option>";
                    break;
                case "kab":
                    $outvar .=  "<option value=\"kab\">Kabyle</option>";
                    break;
                case "kac":
                    $outvar .=  "<option value=\"kac\">Kachin; Jingpho</option>";
                    break;
                case "kal":
                    $outvar .=  "<option value=\"kal\">Kalaallisut; Greenlandic</option>";
                    break;
                case "kam":
                    $outvar .=  "<option value=\"kam\">Kamba</option>";
                    break;
                case "kan":
                    $outvar .=  "<option value=\"kan\">Kannada</option>";
                    break;
                case "kar":
                    $outvar .=  "<option value=\"kar\">Karen languages</option>";
                    break;
                case "kas":
                    $outvar .=  "<option value=\"kas\">Kashmiri</option>";
                    break;
                case "kau":
                    $outvar .=  "<option value=\"kau\">Kanuri</option>";
                    break;
                case "kaw":
                    $outvar .=  "<option value=\"kaw\">Kawi</option>";
                    break;
                case "kaz":
                    $outvar .=  "<option value=\"kaz\">Kazakh</option>";
                    break;
                case "kbd":
                    $outvar .=  "<option value=\"kbd\">Kabardian</option>";
                    break;
                case "kha":
                    $outvar .=  "<option value=\"kha\">Khasi</option>";
                    break;
                case "khi":
                    $outvar .=  "<option value=\"khi\">Khoisan languages</option>";
                    break;
                case "khm":
                    $outvar .=  "<option value=\"khm\">Central Khmer</option>";
                    break;
                case "kho":
                    $outvar .=  "<option value=\"kho\">Khotanese; Sakan</option>";
                    break;
                case "kik":
                    $outvar .=  "<option value=\"kik\">Kikuyu; Gikuyu</option>";
                    break;
                case "kin":
                    $outvar .=  "<option value=\"kin\">Kinyarwanda</option>";
                    break;
                case "kir":
                    $outvar .=  "<option value=\"kir\">Kirghiz; Kyrgyz</option>";
                    break;
                case "kmb":
                    $outvar .=  "<option value=\"kmb\">Kimbundu</option>";
                    break;
                case "kok":
                    $outvar .=  "<option value=\"kok\">Konkani</option>";
                    break;
                case "kom":
                    $outvar .=  "<option value=\"kom\">Komi</option>";
                    break;
                case "kon":
                    $outvar .=  "<option value=\"kon\">Kongo</option>";
                    break;
                case "kor":
                    $outvar .=  "<option value=\"kor\">Korean</option>";
                    break;
                case "kos":
                    $outvar .=  "<option value=\"kos\">Kosraean</option>";
                    break;
                case "kpe":
                    $outvar .=  "<option value=\"kpe\">Kpelle</option>";
                    break;
                case "krc":
                    $outvar .=  "<option value=\"krc\">Karachay-Balkar</option>";
                    break;
                case "krl":
                    $outvar .=  "<option value=\"krl\">Karelian</option>";
                    break;
                case "kro":
                    $outvar .=  "<option value=\"kro\">Kru languages</option>";
                    break;
                case "kru":
                    $outvar .=  "<option value=\"kru\">Kurukh</option>";
                    break;
                case "kua":
                    $outvar .=  "<option value=\"kua\">Kuanyama; Kwanyama</option>";
                    break;
                case "kum":
                    $outvar .=  "<option value=\"kum\">Kumyk</option>";
                    break;
                case "kur":
                    $outvar .=  "<option value=\"kur\">Kurdish</option>";
                    break;
                case "kut":
                    $outvar .=  "<option value=\"kut\">Kutenai</option>";
                    break;
                case "lad":
                    $outvar .=  "<option value=\"lad\">Ladino</option>";
                    break;
                case "lah":
                    $outvar .=  "<option value=\"lah\">Lahnda</option>";
                    break;
                case "lam":
                    $outvar .=  "<option value=\"lam\">Lamba</option>";
                    break;
                case "lao":
                    $outvar .=  "<option value=\"lao\">Lao</option>";
                    break;
                case "lat":
                    $outvar .=  "<option value=\"lat\">Latin</option>";
                    break;
                case "lav":
                    $outvar .=  "<option value=\"lav\">Latvian</option>";
                    break;
                case "lez":
                    $outvar .=  "<option value=\"lez\">Lezghian</option>";
                    break;
                case "lim":
                    $outvar .=  "<option value=\"lim\">Limburgan; Limburger; Limburgish</option>";
                    break;
                case "lin":
                    $outvar .=  "<option value=\"lin\">Lingala</option>";
                    break;
                case "lit":
                    $outvar .=  "<option value=\"lit\">Lithuanian</option>";
                    break;
                case "lol":
                    $outvar .=  "<option value=\"lol\">Mongo</option>";
                    break;
                case "loz":
                    $outvar .=  "<option value=\"loz\">Lozi</option>";
                    break;
                case "ltz":
                    $outvar .=  "<option value=\"ltz\">Luxembourgish; Letzeburgesch</option>";
                    break;
                case "lua":
                    $outvar .=  "<option value=\"lua\">Luba-Lulua</option>";
                    break;
                case "lub":
                    $outvar .=  "<option value=\"lub\">Luba-Katanga</option>";
                    break;
                case "lug":
                    $outvar .=  "<option value=\"lug\">Ganda</option>";
                    break;
                case "lui":
                    $outvar .=  "<option value=\"lui\">Luiseno</option>";
                    break;
                case "lun":
                    $outvar .=  "<option value=\"lun\">Lunda</option>";
                    break;
                case "luo":
                    $outvar .=  "<option value=\"luo\">Luo (Kenya and Tanzania)</option>";
                    break;
                case "lus":
                    $outvar .=  "<option value=\"lus\">Lushai</option>";
                    break;
                case "mac":
                    $outvar .=  "<option value=\"mac\">Macedonian</option>";
                    break;
                case "mad":
                    $outvar .=  "<option value=\"mad\">Madurese</option>";
                    break;
                case "mag":
                    $outvar .=  "<option value=\"mag\">Magahi</option>";
                    break;
                case "mah":
                    $outvar .=  "<option value=\"mah\">Marshallese</option>";
                    break;
                case "mai":
                    $outvar .=  "<option value=\"mai\">Maithili</option>";
                    break;
                case "mak":
                    $outvar .=  "<option value=\"mak\">Makasar</option>";
                    break;
                case "mal":
                    $outvar .=  "<option value=\"mal\">Malayalam</option>";
                    break;
                case "man":
                    $outvar .=  "<option value=\"man\">Mandingo</option>";
                    break;
                case "mao":
                    $outvar .=  "<option value=\"mao\">Maori</option>";
                    break;
                case "map":
                    $outvar .=  "<option value=\"map\">Austronesian languages</option>";
                    break;
                case "mar":
                    $outvar .=  "<option value=\"mar\">Marathi</option>";
                    break;
                case "mas":
                    $outvar .=  "<option value=\"mas\">Masai</option>";
                    break;
                case "may":
                    $outvar .=  "<option value=\"may\">Malay</option>";
                    break;
                case "mdf":
                    $outvar .=  "<option value=\"mdf\">Moksha</option>";
                    break;
                case "mdr":
                    $outvar .=  "<option value=\"mdr\">Mandar</option>";
                    break;
                case "men":
                    $outvar .=  "<option value=\"men\">Mende</option>";
                    break;
                case "mga":
                    $outvar .=  "<option value=\"mga\">Irish, Middle (900-1200)</option>";
                    break;
                case "mic":
                    $outvar .=  "<option value=\"mic\">Mi'kmaq; Micmac</option>";
                    break;
                case "min":
                    $outvar .=  "<option value=\"min\">Minangkabau</option>";
                    break;
                case "mis":
                    $outvar .=  "<option value=\"mis\">Uncoded languages</option>";
                    break;
                case "mkh":
                    $outvar .=  "<option value=\"mkh\">Mon-Khmer languages</option>";
                    break;
                case "mlg":
                    $outvar .=  "<option value=\"mlg\">Malagasy</option>";
                    break;
                case "mlt":
                    $outvar .=  "<option value=\"mlt\">Maltese</option>";
                    break;
                case "mnc":
                    $outvar .=  "<option value=\"mnc\">Manchu</option>";
                    break;
                case "mni":
                    $outvar .=  "<option value=\"mni\">Manipuri</option>";
                    break;
                case "mno":
                    $outvar .=  "<option value=\"mno\">Manobo languages</option>";
                    break;
                case "moh":
                    $outvar .=  "<option value=\"moh\">Mohawk</option>";
                    break;
                case "mon":
                    $outvar .=  "<option value=\"mon\">Mongolian</option>";
                    break;
                case "mos":
                    $outvar .=  "<option value=\"mos\">Mossi</option>";
                    break;
                case "mul":
                    $outvar .=  "<option value=\"mul\">Multiple languages</option>";
                    break;
                case "mun":
                    $outvar .=  "<option value=\"mun\">Munda languages</option>";
                    break;
                case "mus":
                    $outvar .=  "<option value=\"mus\">Creek</option>";
                    break;
                case "mwl":
                    $outvar .=  "<option value=\"mwl\">Mirandese</option>";
                    break;
                case "mwr":
                    $outvar .=  "<option value=\"mwr\">Marwari</option>";
                    break;
                case "myn":
                    $outvar .=  "<option value=\"myn\">Mayan languages</option>";
                    break;
                case "myv":
                    $outvar .=  "<option value=\"myv\">Erzya</option>";
                    break;
                case "nah":
                    $outvar .=  "<option value=\"nah\">Nahuatl languages</option>";
                    break;
                case "nai":
                    $outvar .=  "<option value=\"nai\">North American Indian languages</option>";
                    break;
                case "nap":
                    $outvar .=  "<option value=\"nap\">Neapolitan</option>";
                    break;
                case "nau":
                    $outvar .=  "<option value=\"nau\">Nauru</option>";
                    break;
                case "nav":
                    $outvar .=  "<option value=\"nav\">Navajo; Navaho</option>";
                    break;
                case "nbl":
                    $outvar .=  "<option value=\"nbl\">Ndebele, South; South Ndebele</option>";
                    break;
                case "nde":
                    $outvar .=  "<option value=\"nde\">Ndebele, North; North Ndebele</option>";
                    break;
                case "ndo":
                    $outvar .=  "<option value=\"ndo\">Ndonga</option>";
                    break;
                case "nds":
                    $outvar .=  "<option value=\"nds\">Low German; Low Saxon; German, Low; Saxon, Low</option>";
                    break;
                case "nep":
                    $outvar .=  "<option value=\"nep\">Nepali</option>";
                    break;
                case "new":
                    $outvar .=  "<option value=\"new\">Nepal Bhasa; Newari</option>";
                    break;
                case "nia":
                    $outvar .=  "<option value=\"nia\">Nias</option>";
                    break;
                case "nic":
                    $outvar .=  "<option value=\"nic\">Niger-Kordofanian languages</option>";
                    break;
                case "niu":
                    $outvar .=  "<option value=\"niu\">Niuean</option>";
                    break;
                case "nno":
                    $outvar .=  "<option value=\"nno\">Norwegian Nynorsk; Nynorsk, Norwegian</option>";
                    break;
                case "nob":
                    $outvar .=  "<option value=\"nob\">Bokmål, Norwegian; Norwegian Bokmål</option>";
                    break;
                case "nog":
                    $outvar .=  "<option value=\"nog\">Nogai</option>";
                    break;
                case "non":
                    $outvar .=  "<option value=\"non\">Norse, Old</option>";
                    break;
                case "nor":
                    $outvar .=  "<option value=\"nor\">Norwegian</option>";
                    break;
                case "nqo":
                    $outvar .=  "<option value=\"nqo\">N'Ko</option>";
                    break;
                case "nso":
                    $outvar .=  "<option value=\"nso\">Pedi; Sepedi; Northern Sotho</option>";
                    break;
                case "nub":
                    $outvar .=  "<option value=\"nub\">Nubian languages</option>";
                    break;
                case "nwc":
                    $outvar .=  "<option value=\"nwc\">Classical Newari; Old Newari; Classical Nepal Bhasa</option>";
                    break;
                case "nya":
                    $outvar .=  "<option value=\"nya\">Chichewa; Chewa;  Nyanja</option>";
                    break;
                case "nym":
                    $outvar .=  "<option value=\"nym\">Nyamwezi</option>";
                    break;
                case "nyn":
                    $outvar .=  "<option value=\"nyn\">Nyankole</option>";
                    break;
                case "nyo":
                    $outvar .=  "<option value=\"nyo\">Nyoro</option>";
                    break;
                case "nzi":
                    $outvar .=  "<option value=\"nzi\">Nzima</option>";
                    break;
                case "oci":
                    $outvar .=  "<option value=\"oci\">Occitan (post 1500)</option>";
                    break;
                case "oji":
                    $outvar .=  "<option value=\"oji\">Ojibwa</option>";
                    break;
                case "ori":
                    $outvar .=  "<option value=\"ori\">Oriya</option>";
                    break;
                case "orm":
                    $outvar .=  "<option value=\"orm\">Oromo</option>";
                    break;
                case "osa":
                    $outvar .=  "<option value=\"osa\">Osage</option>";
                    break;
                case "oss":
                    $outvar .=  "<option value=\"oss\">Ossetian; Ossetic</option>";
                    break;
                case "ota":
                    $outvar .=  "<option value=\"ota\">Turkish, Ottoman (1500-1928)</option>";
                    break;
                case "oto":
                    $outvar .=  "<option value=\"oto\">Otomian languages</option>";
                    break;
                case "paa":
                    $outvar .=  "<option value=\"paa\">Papuan languages</option>";
                    break;
                case "pag":
                    $outvar .=  "<option value=\"pag\">Pangasinan</option>";
                    break;
                case "pal":
                    $outvar .=  "<option value=\"pal\">Pahlavi</option>";
                    break;
                case "pam":
                    $outvar .=  "<option value=\"pam\">Pampanga; Kapampangan</option>";
                    break;
                case "pan":
                    $outvar .=  "<option value=\"pan\">Panjabi; Punjabi</option>";
                    break;
                case "pap":
                    $outvar .=  "<option value=\"pap\">Papiamento</option>";
                    break;
                case "pau":
                    $outvar .=  "<option value=\"pau\">Palauan</option>";
                    break;
                case "peo":
                    $outvar .=  "<option value=\"peo\">Persian, Old (ca.600-400 B.C.)</option>";
                    break;
                case "per":
                    $outvar .=  "<option value=\"per\">Persian</option>";
                    break;
                case "phi":
                    $outvar .=  "<option value=\"phi\">Philippine languages</option>";
                    break;
                case "phn":
                    $outvar .=  "<option value=\"phn\">Phoenician</option>";
                    break;
                case "pli":
                    $outvar .=  "<option value=\"pli\">Pali</option>";
                    break;
                case "pol":
                    $outvar .=  "<option value=\"pol\">Polish</option>";
                    break;
                case "pon":
                    $outvar .=  "<option value=\"pon\">Pohnpeian</option>";
                    break;
                case "por":
                    $outvar .=  "<option value=\"por\">Portuguese</option>";
                    break;
                case "pra":
                    $outvar .=  "<option value=\"pra\">Prakrit languages</option>";
                    break;
                case "pro":
                    $outvar .=  "<option value=\"pro\">Provençal, Old (to 1500); Occitan, Old (to 1500)</option>";
                    break;
                case "pus":
                    $outvar .=  "<option value=\"pus\">Pushto; Pashto</option>";
                    break;
                case "que":
                    $outvar .=  "<option value=\"que\">Quechua</option>";
                    break;
                case "raj":
                    $outvar .=  "<option value=\"raj\">Rajasthani</option>";
                    break;
                case "rap":
                    $outvar .=  "<option value=\"rap\">Rapanui</option>";
                    break;
                case "rar":
                    $outvar .=  "<option value=\"rar\">Rarotongan; Cook Islands Maori</option>";
                    break;
                case "roa":
                    $outvar .=  "<option value=\"roa\">Romance languages</option>";
                    break;
                case "roh":
                    $outvar .=  "<option value=\"roh\">Romansh</option>";
                    break;
                case "rom":
                    $outvar .=  "<option value=\"rom\">Romany</option>";
                    break;
                case "rum":
                    $outvar .=  "<option value=\"rum\">Romanian; Moldavian; Moldovan</option>";
                    break;
                case "run":
                    $outvar .=  "<option value=\"run\">Rundi</option>";
                    break;
                case "rup":
                    $outvar .=  "<option value=\"rup\">Aromanian; Arumanian; Macedo-Romanian</option>";
                    break;
                case "rus":
                    $outvar .=  "<option value=\"rus\">Russian</option>";
                    break;
                case "sad":
                    $outvar .=  "<option value=\"sad\">Sandawe</option>";
                    break;
                case "sag":
                    $outvar .=  "<option value=\"sag\">Sango</option>";
                    break;
                case "sah":
                    $outvar .=  "<option value=\"sah\">Yakut</option>";
                    break;
                case "sai":
                    $outvar .=  "<option value=\"sai\">South American Indian languages</option>";
                    break;
                case "sal":
                    $outvar .=  "<option value=\"sal\">Salishan languages</option>";
                    break;
                case "sam":
                    $outvar .=  "<option value=\"sam\">Samaritan Aramaic</option>";
                    break;
                case "san":
                    $outvar .=  "<option value=\"san\">Sanskrit</option>";
                    break;
                case "sas":
                    $outvar .=  "<option value=\"sas\">Sasak</option>";
                    break;
                case "sat":
                    $outvar .=  "<option value=\"sat\">Santali</option>";
                    break;
                case "scn":
                    $outvar .=  "<option value=\"scn\">Sicilian</option>";
                    break;
                case "sco":
                    $outvar .=  "<option value=\"sco\">Scots</option>";
                    break;
                case "sel":
                    $outvar .=  "<option value=\"sel\">Selkup</option>";
                    break;
                case "sem":
                    $outvar .=  "<option value=\"sem\">Semitic languages</option>";
                    break;
                case "sga":
                    $outvar .=  "<option value=\"sga\">Irish, Old (to 900)</option>";
                    break;
                case "sgn":
                    $outvar .=  "<option value=\"sgn\">Sign Languages</option>";
                    break;
                case "shn":
                    $outvar .=  "<option value=\"shn\">Shan</option>";
                    break;
                case "sid":
                    $outvar .=  "<option value=\"sid\">Sidamo</option>";
                    break;
                case "sin":
                    $outvar .=  "<option value=\"sin\">Sinhala; Sinhalese</option>";
                    break;
                case "sio":
                    $outvar .=  "<option value=\"sio\">Siouan languages</option>";
                    break;
                case "sit":
                    $outvar .=  "<option value=\"sit\">Sino-Tibetan languages</option>";
                    break;
                case "sla":
                    $outvar .=  "<option value=\"sla\">Slavic languages</option>";
                    break;
                case "slo":
                    $outvar .=  "<option value=\"slo\">Slovak</option>";
                    break;
                case "slv":
                    $outvar .=  "<option value=\"slv\">Slovenian</option>";
                    break;
                case "sma":
                    $outvar .=  "<option value=\"sma\">Southern Sami</option>";
                    break;
                case "sme":
                    $outvar .=  "<option value=\"sme\">Northern Sami</option>";
                    break;
                case "smi":
                    $outvar .=  "<option value=\"smi\">Sami languages</option>";
                    break;
                case "smj":
                    $outvar .=  "<option value=\"smj\">Lule Sami</option>";
                    break;
                case "smn":
                    $outvar .=  "<option value=\"smn\">Inari Sami</option>";
                    break;
                case "smo":
                    $outvar .=  "<option value=\"smo\">Samoan</option>";
                    break;
                case "sms":
                    $outvar .=  "<option value=\"sms\">Skolt Sami</option>";
                    break;
                case "sna":
                    $outvar .=  "<option value=\"sna\">Shona</option>";
                    break;
                case "snd":
                    $outvar .=  "<option value=\"snd\">Sindhi</option>";
                    break;
                case "snk":
                    $outvar .=  "<option value=\"snk\">Soninke</option>";
                    break;
                case "sog":
                    $outvar .=  "<option value=\"sog\">Sogdian</option>";
                    break;
                case "som":
                    $outvar .=  "<option value=\"som\">Somali</option>";
                    break;
                case "son":
                    $outvar .=  "<option value=\"son\">Songhai languages</option>";
                    break;
                case "sot":
                    $outvar .=  "<option value=\"sot\">Sotho, Southern</option>";
                    break;
                case "spa":
                    $outvar .=  "<option value=\"spa\">Spanish; Castilian</option>";
                    break;
                case "srd":
                    $outvar .=  "<option value=\"srd\">Sardinian</option>";
                    break;
                case "srn":
                    $outvar .=  "<option value=\"srn\">Sranan Tongo</option>";
                    break;
                case "srp":
                    $outvar .=  "<option value=\"srp\">Serbian</option>";
                    break;
                case "srr":
                    $outvar .=  "<option value=\"srr\">Serer</option>";
                    break;
                case "ssa":
                    $outvar .=  "<option value=\"ssa\">Nilo-Saharan languages</option>";
                    break;
                case "ssw":
                    $outvar .=  "<option value=\"ssw\">Swati</option>";
                    break;
                case "suk":
                    $outvar .=  "<option value=\"suk\">Sukuma</option>";
                    break;
                case "sun":
                    $outvar .=  "<option value=\"sun\">Sundanese</option>";
                    break;
                case "sus":
                    $outvar .=  "<option value=\"sus\">Susu</option>";
                    break;
                case "sux":
                    $outvar .=  "<option value=\"sux\">Sumerian</option>";
                    break;
                case "swa":
                    $outvar .=  "<option value=\"swa\">Swahili</option>";
                    break;
                case "swe":
                    $outvar .=  "<option value=\"swe\">Swedish</option>";
                    break;
                case "syc":
                    $outvar .=  "<option value=\"syc\">Classical Syriac</option>";
                    break;
                case "syr":
                    $outvar .=  "<option value=\"syr\">Syriac</option>";
                    break;
                case "tah":
                    $outvar .=  "<option value=\"tah\">Tahitian</option>";
                    break;
                case "tai":
                    $outvar .=  "<option value=\"tai\">Tai languages</option>";
                    break;
                case "tam":
                    $outvar .=  "<option value=\"tam\">Tamil</option>";
                    break;
                case "tat":
                    $outvar .=  "<option value=\"tat\">Tatar</option>";
                    break;
                case "tel":
                    $outvar .=  "<option value=\"tel\">Telugu</option>";
                    break;
                case "tem":
                    $outvar .=  "<option value=\"tem\">Timne</option>";
                    break;
                case "ter":
                    $outvar .=  "<option value=\"ter\">Tereno</option>";
                    break;
                case "tet":
                    $outvar .=  "<option value=\"tet\">Tetum</option>";
                    break;
                case "tgk":
                    $outvar .=  "<option value=\"tgk\">Tajik</option>";
                    break;
                case "tgl":
                    $outvar .=  "<option value=\"tgl\">Tagalog</option>";
                    break;
                case "tha":
                    $outvar .=  "<option value=\"tha\">Thai</option>";
                    break;
                case "tib":
                    $outvar .=  "<option value=\"tib\">Tibetan</option>";
                    break;
                case "tig":
                    $outvar .=  "<option value=\"tig\">Tigre</option>";
                    break;
                case "tir":
                    $outvar .=  "<option value=\"tir\">Tigrinya</option>";
                    break;
                case "tiv":
                    $outvar .=  "<option value=\"tiv\">Tiv</option>";
                    break;
                case "tkl":
                    $outvar .=  "<option value=\"tkl\">Tokelau</option>";
                    break;
                case "tlh":
                    $outvar .=  "<option value=\"tlh\">Klingon; tlhIngan-Hol</option>";
                    break;
                case "tli":
                    $outvar .=  "<option value=\"tli\">Tlingit</option>";
                    break;
                case "tmh":
                    $outvar .=  "<option value=\"tmh\">Tamashek</option>";
                    break;
                case "tog":
                    $outvar .=  "<option value=\"tog\">Tonga (Nyasa)</option>";
                    break;
                case "ton":
                    $outvar .=  "<option value=\"ton\">Tonga (Tonga Islands)</option>";
                    break;
                case "tpi":
                    $outvar .=  "<option value=\"tpi\">Tok Pisin</option>";
                    break;
                case "tsi":
                    $outvar .=  "<option value=\"tsi\">Tsimshian</option>";
                    break;
                case "tsn":
                    $outvar .=  "<option value=\"tsn\">Tswana</option>";
                    break;
                case "tso":
                    $outvar .=  "<option value=\"tso\">Tsonga</option>";
                    break;
                case "tuk":
                    $outvar .=  "<option value=\"tuk\">Turkmen</option>";
                    break;
                case "tum":
                    $outvar .=  "<option value=\"tum\">Tumbuka</option>";
                    break;
                case "tup":
                    $outvar .=  "<option value=\"tup\">Tupi languages</option>";
                    break;
                case "tur":
                    $outvar .=  "<option value=\"tur\">Turkish</option>";
                    break;
                case "tut":
                    $outvar .=  "<option value=\"tut\">Altaic languages</option>";
                    break;
                case "tvl":
                    $outvar .=  "<option value=\"tvl\">Tuvalu</option>";
                    break;
                case "twi":
                    $outvar .=  "<option value=\"twi\">Twi</option>";
                    break;
                case "tyv":
                    $outvar .=  "<option value=\"tyv\">Tuvinian</option>";
                    break;
                case "udm":
                    $outvar .=  "<option value=\"udm\">Udmurt</option>";
                    break;
                case "uga":
                    $outvar .=  "<option value=\"uga\">Ugaritic</option>";
                    break;
                case "uig":
                    $outvar .=  "<option value=\"uig\">Uighur; Uyghur</option>";
                    break;
                case "ukr":
                    $outvar .=  "<option value=\"ukr\">Ukrainian</option>";
                    break;
                case "umb":
                    $outvar .=  "<option value=\"umb\">Umbundu</option>";
                    break;
                case "und":
                    $outvar .=  "<option value=\"und\">Undetermined</option>";
                    break;
                case "urd":
                    $outvar .=  "<option value=\"urd\">Urdu</option>";
                    break;
                case "uzb":
                    $outvar .=  "<option value=\"uzb\">Uzbek</option>";
                    break;
                case "vai":
                    $outvar .=  "<option value=\"vai\">Vai</option>";
                    break;
                case "ven":
                    $outvar .=  "<option value=\"ven\">Venda</option>";
                    break;
                case "vie":
                    $outvar .=  "<option value=\"vie\">Vietnamese</option>";
                    break;
                case "vol":
                    $outvar .=  "<option value=\"vol\">Volapük</option>";
                    break;
                case "vot":
                    $outvar .=  "<option value=\"vot\">Votic</option>";
                    break;
                case "wak":
                    $outvar .=  "<option value=\"wak\">Wakashan languages</option>";
                    break;
                case "wal":
                    $outvar .=  "<option value=\"wal\">Wolaitta; Wolaytta</option>";
                    break;
                case "war":
                    $outvar .=  "<option value=\"war\">Waray</option>";
                    break;
                case "was":
                    $outvar .=  "<option value=\"was\">Washo</option>";
                    break;
                case "wel":
                    $outvar .=  "<option value=\"wel\">Welsh</option>";
                    break;
                case "wen":
                    $outvar .=  "<option value=\"wen\">Sorbian languages</option>";
                    break;
                case "wln":
                    $outvar .=  "<option value=\"wln\">Walloon</option>";
                    break;
                case "wol":
                    $outvar .=  "<option value=\"wol\">Wolof</option>";
                    break;
                case "xal":
                    $outvar .=  "<option value=\"xal\">Kalmyk; Oirat</option>";
                    break;
                case "xho":
                    $outvar .=  "<option value=\"xho\">Xhosa</option>";
                    break;
                case "yao":
                    $outvar .=  "<option value=\"yao\">Yao</option>";
                    break;
                case "yap":
                    $outvar .=  "<option value=\"yap\">Yapese</option>";
                    break;
                case "yid":
                    $outvar .=  "<option value=\"yid\">Yiddish</option>";
                    break;
                case "yor":
                    $outvar .=  "<option value=\"yor\">Yoruba</option>";
                    break;
                case "ypk":
                    $outvar .=  "<option value=\"ypk\">Yupik languages</option>";
                    break;
                case "zap":
                    $outvar .=  "<option value=\"zap\">Zapotec</option>";
                    break;
                case "zbl":
                    $outvar .=  "<option value=\"zbl\">Blissymbols; Blissymbolics; Bliss</option>";
                    break;
                case "zen":
                    $outvar .=  "<option value=\"zen\">Zenaga</option>";
                    break;
                case "zgh":
                    $outvar .=  "<option value=\"zgh\">Standard Moroccan Tamazight</option>";
                    break;
                case "zha":
                    $outvar .=  "<option value=\"zha\">Zhuang; Chuang</option>";
                    break;
                case "znd":
                    $outvar .=  "<option value=\"znd\">Zande languages</option>";
                    break;
                case "zul":
                    $outvar .=  "<option value=\"zul\">Zulu</option>";
                    break;
                case "zun":
                    $outvar .=  "<option value=\"zun\">Zuni</option>";
                    break;
                case "zxx":
                    $outvar .=  "<option value=\"zxx\">No linguistic content;
                    break; Not applicable</option>";
                    break;
                case "zza":
                    $outvar .=  "<option value=\"zza\">Zaza; Dimili; Dimli; Kirdki; Kirmanjki; Zazaki</option>";
                    break;
            }
        }
        return $outvar;
    }
}
