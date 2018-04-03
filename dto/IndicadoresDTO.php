<?php

require_once dirname(__FILE__).'/../../../SEI.php';

class IndicadoresDTO extends InfraDTO {

  public function getStrNomeTabela() {
     return "md_estatísticas_indicadores";
  }

  public function montar() {

    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'VersaoSEI', 'versao_sei');
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'VersaoPHP', 'versao_php');
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'TamanhoFileSystem', 'tamanho_file_system');
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'Plugins', 'plugins');
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'QuantidadeUnidades', 'quantidade_unidades');
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'TamanhoDocumentosExternos', 'tamanho_docs_externos');
  }
}
