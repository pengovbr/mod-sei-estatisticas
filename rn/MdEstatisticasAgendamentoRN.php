<?
require_once dirname(__FILE__).'/../../../SEI.php';


class MdEstatisticasAgendamentoRN extends InfraRN {

  protected function inicializarObjInfraIBanco(){
    return BancoSEI::getInstance();
  }

  public function coletarIndicadores() {
    $coletarRN = new MdEstatisticasColetarRN();
    $objIndicadoresDTO = $coletarRN->coletarIndicadores();
  }

}
?>
