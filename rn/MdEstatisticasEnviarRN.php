<?
require_once dirname(__FILE__) . '/../../../SEI.php';

class MdEstatisticasEnviarRN extends InfraRN
{

    public function __construct() {
        parent::__construct();

        $objConfiguracaoSEI = ConfiguracaoSEI::getInstance();
        $url = $objConfiguracaoSEI->getValor('MdEstatisticas', 'url', false, 'http://estatisticas.planejamento.gov.br');
        $this->url = $url . '/api/estatisticas';
        $this->urllogin = $url . '/login';
        $this->orgaoSigla = $objConfiguracaoSEI->getValor('MdEstatisticas', 'sigla', false, '');
        $this->orgaoSenha = $objConfiguracaoSEI->getValor('MdEstatisticas', 'chave', false, '');
        $this->header = array('Content-Type: application/json');
    }

    protected function inicializarObjInfraIBanco() {
        return BancoSEI::getInstance();
    }

    public function enviarIndicadores($indicadores) {
        return $this->doPost($this->url, $indicadores);
    }

    public function obterUltimoAcesso() {
        $data = $this->doGet($this->url . '/acessos/ultimo?sigla=' . $this->orgaoSigla, false);
        return date($data);
    }

    public function obterUltimoRecurso() {
        $data = $this->doGet($this->url . '/recursos/ultimo?sigla=' . $this->orgaoSigla, false);
        return date($data);
    }

    public function enviarAcessos($acessos, $id) {
        $url = $this->url . '/acessos';
        $obj = array(
            id => $id,
            acessosUsuarios => $acessos
        );
        return $this->doPost($url, $obj, false);
    }

    public function enviarVelocidades($velocidades, $id) {
        $url = $this->url . '/velocidades';
        $obj = array(
            id => $id,
            velocidades => $velocidades
        );
        return $this->doPost($url, $obj, false);
    }

    public function enviarSistemasUsuarios($sistemasOperacionaisUsuarios, $id) {
        $url = $this->url . '/sistemasoperacionais';
        $obj = array(
            id => $id,
            sistemasOperacionaisUsuarios => $sistemasOperacionaisUsuarios
        );
        return $this->doPost($url, $obj, false);
    }

    public function enviarNavegadores($navegadores, $id) {
        $url = $this->url . '/navegadores';
        $obj = array(
            id => $id,
            navegadores => $navegadores
        );
        return $this->doPost($url, $obj, false);
    }

    public function enviarLogsErro($logs, $id) {
        $url = $this->url . '/logserro';
        $obj = array(
            id => $id,
            logsErro => $logs
        );
        return $this->doPost($url, $obj, false);
    }

    public function enviarRecursos($recursos, $id) {
        $url = $this->url . '/recursos';
        $obj = array(
            id => $id,
            recursos => $recursos
        );
        return $this->doPost($url, $obj, false);
    }

    public function enviarHashs($hashs, $id) {
      $url = $this->url . '/fileshashs';
      $obj = array(
          id => $id,
          filesHashs => $hashs
      );
      return $this->doPost($url, $obj, false);
  }

    public function autenticar() {
        $json = array(
            username => $this->orgaoSigla,
            password => $this->orgaoSenha
        );
        $data = json_encode($json);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->urllogin);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HEADER, true);
        $output = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        if ($info['http_code'] == 200) {
            $output = explode("\r\n", $output);
            foreach ($output as $value) {
                if (strpos($value, 'Authorization') !== false) {
                    $this->header[] = $value;
                    return true;
                }
            }
        }
        return false;
    }

    private function doPost($url, $json, $isjson = true) {
        $data = json_encode($json);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);
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
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);
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
