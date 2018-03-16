<?
require_once dirname(__FILE__).'/../../../SEI.php';


class MdEstatisticasVersaoRN extends InfraRN {

  const MD_ESTATISTICAS_VERSAO = "1.0.0";

  public function __construct(){
    parent::__construct();
  }

  protected function inicializarObjInfraIBanco(){
    return BancoSEI::getInstance();
  }

  private function inicializar($strTitulo){

    ini_set('max_execution_time','0');
    ini_set('memory_limit','-1');
    ini_set('mssql.timeout','0');

    InfraDebug::getInstance()->setBolLigado(false);
    InfraDebug::getInstance()->setBolDebugInfra(true);
    InfraDebug::getInstance()->setBolEcho(true);
    InfraDebug::getInstance()->limpar();

    $this->numSeg = InfraUtil::verificarTempoProcessamento();

    $this->logar($strTitulo);
  }

  private function logar($strMsg){
    InfraDebug::getInstance()->gravar($strMsg);
  }

  private function finalizar($strMsg=null, $bolErro){

    if (!$bolErro) {
      $this->numSeg = InfraUtil::verificarTempoProcessamento($this->numSeg);
      $this->logar('TEMPO TOTAL DE EXECUCAO: ' . $this->numSeg . ' s');
    }else{
      $strMsg = 'ERRO: '.$strMsg;
    }

    if ($strMsg!=null){
      $this->logar($strMsg);
    }

    InfraDebug::getInstance()->setBolLigado(false);
    InfraDebug::getInstance()->setBolDebugInfra(false);
    InfraDebug::getInstance()->setBolEcho(false);
    $this->numSeg = 0;
    die;
  }

  protected function atualizarVersaoConectado(){
    try{

      $this->inicializar('INICIANDO ATUALIZACAO MÓDULO DE ESTATÍSTICAS ' . SEI_VERSAO);

      $objInfraParametro = new InfraParametro(BancoSEI::getInstance());

      $strVersaoAtual = $objInfraParametro->getValor('MD_ESTATISTICAS_VERSAO', false);

      if (substr($strVersaoAtual,0,3) == substr(MdEstatisticasVersaoRN::MD_ESTATISTICAS_VERSAO,0,3)) {
        $this->finalizar('VERSAO JA CONSTA COMO ATUALIZADA',true);
      }


      // Versão inicial 1.0.0
      if (InfraString::isBolVazia($strVersaoAtual)){
        //Rotinas de criação de tabelas do módulo
        //...

        BancoSEI::getInstance()->executarSql('update infra_parametro set valor=\'' . MdEstatisticasVersaoRN::MD_ESTATISTICAS_VERSAO . '\' where nome=\'MD_ESTATISTICAS_VERSAO\'');
      }

      //Versão 1.1.0
      // if (substr($strVersaoAtual,0,3) == '1.0' && MdEstatisticasVersaoRN::MD_ESTATISTICAS_VERSAO == '1.1.0') {
      //   //Rotinas de criação/atualização de tabelas do módulo
      //   //...

      //   BancoSEI::getInstance()->executarSql('update infra_parametro set valor=\'' . MdEstatisticasVersaoRN::MD_ESTATISTICAS_VERSAO . '\' where nome=\'MD_ESTATISTICAS_VERSAO\'');
      // }

      $this->finalizar('FIM',false);

    }catch(Exception $e){
      InfraDebug::getInstance()->setBolLigado(false);
      InfraDebug::getInstance()->setBolDebugInfra(false);
      InfraDebug::getInstance()->setBolEcho(false);
      throw new InfraException('Erro atualizando versão.', $e);
    }
  }
}
?>
