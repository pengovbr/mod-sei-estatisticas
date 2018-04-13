<?
require_once dirname(__FILE__).'/../../../SEI.php';


class MdEstatisticasAgendamentoRN extends InfraRN {

  protected function inicializarObjInfraIBanco(){
    return BancoSEI::getInstance();
  }

  public function coletarIndicadores() {

    InfraDebug::getInstance()->setBolLigado(true);
    InfraDebug::getInstance()->setBolDebugInfra(false);
    InfraDebug::getInstance()->setBolEcho(false);
    InfraDebug::getInstance()->limpar();

    $indicadores = (new MdEstatisticasColetarRN())-> coletarIndicadores();
    (new MdEstatisticasEnviarRN())-> enviarIndicadores($indicadores);

    LogSEI::getInstance()->gravar(InfraDebug::getInstance()->getStrDebug(),InfraLog::$INFORMACAO);
  }

}
?>
