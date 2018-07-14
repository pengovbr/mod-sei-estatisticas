<?
require_once dirname(__FILE__) . '/../../../SEI.php';

class MdEstatisticasEnviarRN extends InfraRN
{

    public function __construct() {
        parent::__construct();

        $objConfiguracaoSEI = ConfiguracaoSEI::getInstance();
        $this->url = $objConfiguracaoSEI->getValor('MdEstatisticas', 'url', false, 'http://estatisticas.planejamento.gov.br');
        $this->orgaoSigla = $objConfiguracaoSEI->getValor('MdEstatisticas', 'sigla', false, '');
    }

    protected function inicializarObjInfraIBanco() {
        return BancoSEI::getInstance();
    }

    public function enviarIndicadores($indicadores) {
        return $this->doPost($this->url, $indicadores);
    }

    public function obterUltimoAcesso() {
        $data = $this->doGet($this->url . '/ultimoacesso?sigla=' . $this->orgaoSigla, false);
        return date($data);
    }

    public function enviarAcessos($acessos, $id) {
        $url = $this->url . '/acessos';
        InfraDebug::getInstance()->gravar('URL: ' . $url, InfraLog::$INFORMACAO);
        $obj = array(
            id => $id,
            acessosUsuarios => $acessos
        );
        InfraDebug::getInstance()->gravar('URL: ' . json_encode($obj), InfraLog::$INFORMACAO);
        return $this->doPost($url, $obj, false);
    }

    public function enviarVelocidades($velocidades, $id) {
        $url = $this->url . '/velocidades';
        InfraDebug::getInstance()->gravar('URL: ' . $url, InfraLog::$INFORMACAO);
        $obj = array(
            id => $id,
            velocidades => $velocidades
        );
        InfraDebug::getInstance()->gravar('URL: ' . json_encode($obj), InfraLog::$INFORMACAO);
        return $this->doPost($url, $obj, false);
    }

    public function enviarSistemasUsuarios($sistemasOperacionaisUsuarios, $id) {
        $url = $this->url . '/sistemasoperacionais';
        InfraDebug::getInstance()->gravar('URL: ' . $url, InfraLog::$INFORMACAO);
        $obj = array(
            id => $id,
            sistemasOperacionaisUsuarios => $sistemasOperacionaisUsuarios
        );
        InfraDebug::getInstance()->gravar('URL: ' . json_encode($obj), InfraLog::$INFORMACAO);
        return $this->doPost($url, $obj, false);
    }

    public function enviarNavegadores($navegadores, $id) {
        $url = $this->url . '/navegadores';
        InfraDebug::getInstance()->gravar('URL: ' . $url, InfraLog::$INFORMACAO);
        $obj = array(
            id => $id,
            navegadores => $navegadores
        );
        InfraDebug::getInstance()->gravar('URL: ' . json_encode($obj), InfraLog::$INFORMACAO);
        return $this->doPost($url, $obj, false);
    }

    public function enviarLogsErro($logs, $id) {
        $url = $this->url . '/logserro';
        InfraDebug::getInstance()->gravar('URL: ' . $url, InfraLog::$INFORMACAO);
        $obj = array(
            id => $id,
            logsErro => $logs
        );
        InfraDebug::getInstance()->gravar('URL: ' . json_encode($obj), InfraLog::$INFORMACAO);
        return $this->doPost($url, $obj, false);
    }

    private function doPost($url, $json, $isjson = true) {
        $data = json_encode($json);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json'
        ));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $output = curl_exec($ch);
        curl_close($ch);

        if ($isjson) {
            return json_decode($output, true);
        }
        return $output;
    }

    private function doGet($url, $isjson = true) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        $output = curl_exec($ch);
        curl_close($ch);

        if ($isjson) {
            return json_decode($output, true);
        }
        return $output;
    }
}
?>
