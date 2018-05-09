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

      InfraDebug::getInstance()->gravar('URL: ' . $url, InfraLog::$INFORMACAO);

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
      $output = curl_exec($ch);

      InfraDebug::getInstance()->gravar('Output: ' . $output, InfraLog::$INFORMACAO);
      curl_close($ch);

      return true;

    } catch(Exception $e) {
      InfraDebug::getInstance()->setBolLigado(false);
      InfraDebug::getInstance()->setBolDebugInfra(false);
      InfraDebug::getInstance()->setBolEcho(false);
      throw new InfraException('Erro processando estatísticas do sistema.',$e);
    }
  }

}
?>
