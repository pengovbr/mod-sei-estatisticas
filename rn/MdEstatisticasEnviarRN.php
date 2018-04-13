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
