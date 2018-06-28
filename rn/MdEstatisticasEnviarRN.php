<?
require_once dirname(__FILE__).'/../../../SEI.php';


class MdEstatisticasEnviarRN extends InfraRN {

  public function __construct(){
    parent::__construct();
  }

  protected function inicializarObjInfraIBanco(){
    return BancoSEI::getInstance();
  }

  public function enviarIndicadores($indicadores) {

    try {

      $json = json_encode($indicadores);
      InfraDebug::getInstance()->gravar('JSON: ' . $json, InfraLog::$INFORMACAO);

      $objConfiguracaoSEI = ConfiguracaoSEI::getInstance();
      $url = $objConfiguracaoSEI->getValor('MdEstatisticas','url', false, 'http://estatisticas.planejamento.gov.br');
      $orgaoSigla = $objConfiguracaoSEI->getValor('MdEstatisticas','sigla', false, '');

      $output = $this->doPost($url, $json);
      $id = $output['id'];
      InfraDebug::getInstance()->gravar('Output: ' . json_encode($output), InfraLog::$INFORMACAO);
      InfraDebug::getInstance()->gravar('iD: ' . $id, InfraLog::$INFORMACAO);

      $data = $this->doGet($url . '/ultimoacesso?sigla=' . $orgaoSigla);
      $data = date($data);
      InfraDebug::getInstance()->gravar('Data: ' . $data, InfraLog::$INFORMACAO);

      return true;

    } catch(Exception $e) {
      InfraDebug::getInstance()->setBolLigado(false);
      InfraDebug::getInstance()->setBolDebugInfra(false);
      InfraDebug::getInstance()->setBolEcho(false);
      throw new InfraException('Erro processando estatísticas do sistema.',$e);
    }
  }

  private function doPost($url, $json) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    $output = curl_exec($ch);
    curl_close($ch);
    return json_decode($output, true);
  }

  private function doGet($url, $isjson=false) {
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
