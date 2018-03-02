<?php

require_once dirname(__FILE__).'/../../../SEI.php';

class IndicadoresDTO extends InfraDTO {

  public function getStrNomeTabela() {
     return "md_estatísticas_indicadores";
  }

  public function montar() {

    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'VersaoSEI', 'versao_sei');
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'QuantidadeUnidades', 'quantidade_unidades');
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'TamanhoDocumentosExternos', 'tamanho_docs_externos');

    // $this->adicionarAtributo(InfraDTO::$PREFIXO_NUM, 'NumeroDeIdentificacaoDaEstrutura');
    // $this->adicionarAtributo(InfraDTO::$PREFIXO_STR, 'Nome');
    // $this->adicionarAtributo(InfraDTO::$PREFIXO_STR, 'Sigla');
    // $this->adicionarAtributo(InfraDTO::$PREFIXO_BOL, 'Ativo');
    // $this->adicionarAtributo(InfraDTO::$PREFIXO_BOL, 'AptoParaReceberTramites');
    // $this->adicionarAtributo(InfraDTO::$PREFIXO_STR, 'CodigoNoOrgaoEntidade');
    // $this->adicionarAtributo(InfraDTO::$PREFIXO_ARR, 'Hierarquia');
  }
}
