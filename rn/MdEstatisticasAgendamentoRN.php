<?
require_once dirname(__FILE__).'/../../../SEI.php';

class MdEstatisticasAgendamentoRN extends InfraRN {

  protected function inicializarObjInfraIBanco(){
    return BancoSEI::getInstance();
  }

  public function coletarIndicadores() {
    
    try {

        InfraDebug::getInstance()->setBolLigado(true);
        InfraDebug::getInstance()->setBolDebugInfra(false);
        InfraDebug::getInstance()->setBolEcho(false);
        InfraDebug::getInstance()->limpar();


        //IMPLEMENTAÇÃO DA EXTRAÇÃO DE INDICADORES
        //1) Criar o objeto DTO representativo dos indicaores
        $objIndicadoresDTO = new IndicadoresDTO();

        //2) Preencher cada indicador do sistema
        $objIndicadoresDTO->setStrVersaoSEI($this->obterVersaoSEI());
        $objIndicadoresDTO->setNumQuantidadeUnidades($this->obterQuantidadeUnidades());
        $objIndicadoresDTO->setNumTamanhoDocumentosExternos($this->obterTamanhoTotalDocumentosExternos());

        
        //...

        //3) Salvar indicador no banco de dados ????
        //
        
        //4) Enviar indicadores para webservice
        //
                       
        
        LogSEI::getInstance()->gravar(InfraDebug::getInstance()->getStrDebug(),InfraLog::$INFORMACAO);

    } catch(Exception $e) {
      InfraDebug::getInstance()->setBolLigado(false);
      InfraDebug::getInstance()->setBolDebugInfra(false);
      InfraDebug::getInstance()->setBolEcho(false);
      throw new InfraException('Erro processando estatísticas do sistema.',$e);
    }
  }


  private function obterVersaoSEI(){
    InfraDebug::getInstance()->gravar('SEIXX - Versão SEI: ' . SEI_VERSAO, InfraLog::$INFORMACAO);
    return SEI_VERSAO;
  }

  private function obterQuantidadeUnidades(){

    $objUnidadeRN = new UnidadeRN();
    $numQuantidadeUnidades = $objUnidadeRN->contarRN0128(new UnidadeDTO());

    InfraDebug::getInstance()->gravar('SEI11 - Quantidade Unidades: ' . $numQuantidadeUnidades, InfraLog::$INFORMACAO);        
    return $numQuantidadeUnidades;
  }


  private function obterTamanhoTotalDocumentosExternos(){

    $query = "select sum(tamanho) as tamanho from anexo where sin_ativo = 'S'";
    $rs = BancoSEI::getInstance()->consultarSql($query);
    $tamanho = (count($rs) && isset($rs[0]['tamanho'])) ? $rs[0]['tamanho'] : 0;

    InfraDebug::getInstance()->gravar('SEI12 - Tamanho Documentos Externos: ' . $tamanho, InfraLog::$INFORMACAO);        
    return $tamanho;
  }


}
?>