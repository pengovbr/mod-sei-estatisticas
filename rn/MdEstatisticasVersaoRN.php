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

  protected function fixCredencialAssinaturaControlado() {
    try {
      InfraDebug::getInstance()->gravar('ATUALIZANDO CREDENCIAIS DE ASSINATURA...');

      //corrige situações onde o processo continua no Controle de Processos após renúncia ou conclusão

      //busca atividades de concessão de credencial para assinatura onde não existem mais os documentos
      $sql = 'select atividade.id_atividade, protocolo.protocolo_formatado,' . BancoSEI::getInstance()->formatarSelecaoDbl('atividade', 'id_protocolo', null) . ',atividade.id_usuario,atividade.id_unidade, atributo_andamento.id_origem
        from atividade, atributo_andamento, protocolo, acesso
        where atividade.id_atividade=atributo_andamento.id_atividade
        and atividade.id_tarefa=' . TarefaRN::$TI_CONCESSAO_CREDENCIAL_ASSINATURA . '
        and atributo_andamento.nome=' . BancoSEI::getInstance()->formatarGravacaoStr('DOCUMENTO') . '
        and not exists (select documento.id_documento from documento where documento.id_documento=atributo_andamento.id_origem)
        and atividade.id_protocolo=protocolo.id_protocolo
        and protocolo.sta_nivel_acesso_global=' . BancoSEI::getInstance()->formatarGravacaoStr(ProtocoloRN::$NA_SIGILOSO) . '
        and acesso.id_protocolo=atividade.id_protocolo
        and acesso.id_unidade=atividade.id_unidade
        and acesso.id_usuario=atividade.id_usuario
        and acesso.sta_tipo=' . BancoSEI::getInstance()->formatarGravacaoStr(AcessoRN::$TA_CREDENCIAL_ASSINATURA_PROCESSO);

      $rs1 = BancoSEI::getInstance()->consultarSql($sql);

      InfraDebug::getInstance()->setBolDebugInfra(false);

      if (count($rs1)) {

        foreach ($rs1 as $item) {

          $numIdAtividade = $item['id_atividade'];
          $dblIdProtocolo = $item['id_protocolo'];
          $numIdUsuario = $item['id_usuario'];
          $numIdUnidade = $item['id_unidade'];
          $strIdOrigem = $item['id_origem'];

          //verificando se o usuario/unidade possui outras credenciais de assinatura em documentos existentes do processo

          $sql = 'select count(*) as total
            from atividade, atributo_andamento
            where atividade.id_atividade=atributo_andamento.id_atividade
            and atividade.id_tarefa=' . TarefaRN::$TI_CONCESSAO_CREDENCIAL_ASSINATURA . '
            and atributo_andamento.nome=' . BancoSEI::getInstance()->formatarGravacaoStr('DOCUMENTO') . '
            and exists (select documento.id_documento from documento where documento.id_documento=atributo_andamento.id_origem)
            and atividade.id_protocolo=' . $dblIdProtocolo . '
            and atividade.id_usuario=' . $numIdUsuario . '
            and atividade.id_unidade=' . $numIdUnidade;

          $rs2 = BancoSEI::getInstance()->consultarSql($sql);

          if ($rs2[0]['total'] == 0) {

            InfraDebug::getInstance()->gravar($item['protocolo_formatado'].', credencial de assinatura '.$numIdAtividade);

            //remove registros de acesso associados com credencial de assinatura para o usuario/unidade/processo


            BancoSEI::getInstance()->executarSql('delete from acesso
              where id_usuario=' . $numIdUsuario . '
              and id_unidade=' . $numIdUnidade . '
              and (id_protocolo=' . $dblIdProtocolo . ' or id_protocolo in (select id_documento from documento where id_procedimento=' . $dblIdProtocolo . '))
              and sta_tipo=' . BancoSEI::getInstance()->formatarGravacaoStr(AcessoRN::$TA_CREDENCIAL_ASSINATURA_PROCESSO));


            //busca identificador da última atividade do usuário no processo
            $rs3 = BancoSEI::getInstance()->consultarSql('select max(id_atividade) as ultima
              from atividade
              where id_usuario=' . $numIdUsuario . '
              and id_unidade=' . $numIdUnidade . '
              and id_protocolo=' . $dblIdProtocolo);

            if ($rs3[0]['ultima']) {

              //busca dados da ultima atividade
              $rs4 = BancoSEI::getInstance()->consultarSql('select id_atividade, id_tarefa, dth_abertura, dth_conclusao, id_usuario, id_unidade
                from atividade
                where id_atividade=' . $rs3[0]['ultima']);

              //se a última ação do usuário foi renúnciar ao processo mas ele continua aberto no Controle de Processos
              if ($rs4[0]['id_tarefa'] == TarefaRN::$TI_PROCESSO_RENUNCIA_CREDENCIAL && BancoSEI::getInstance()->formatarLeituraDth($rs4[0]['dth_conclusao']) == null) {

                //finaliza pendencia para sumir do controle de processos
                BancoSEI::getInstance()->executarSql('update atividade
                  set dth_conclusao=' . BancoSEI::getInstance()->formatarGravacaoDth(BancoSEI::getInstance()->formatarLeituraDth($rs4[0]['dth_abertura'])) . '
                  where id_atividade=' . $rs4[0]['id_atividade']);
              }
            }
          }

          //busca atividade de exclusão do documento
          $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
          $objAtributoAndamentoDTO->retNumIdAtividade();
          $objAtributoAndamentoDTO->retDthAberturaAtividade();
          $objAtributoAndamentoDTO->retNumIdUsuarioOrigemAtividade();
          $objAtributoAndamentoDTO->retStrSiglaUsuarioOrigemAtividade();
          $objAtributoAndamentoDTO->retStrNomeUsuarioOrigemAtividade();
          $objAtributoAndamentoDTO->retNumIdUnidadeOrigemAtividade();
          $objAtributoAndamentoDTO->setStrNome('DOCUMENTO');
          $objAtributoAndamentoDTO->setStrIdOrigem($strIdOrigem);
          $objAtributoAndamentoDTO->setNumIdTarefaAtividade(TarefaRN::$TI_EXCLUSAO_DOCUMENTO);
          $objAtributoAndamentoDTO->setDblIdProtocoloAtividade($dblIdProtocolo);

          $objAtributoAndamentoRN = new AtributoAndamentoRN();
          $objAtributoAndamentoDTOExclusao = $objAtributoAndamentoRN->consultarRN1366($objAtributoAndamentoDTO);

          if ($objAtributoAndamentoDTOExclusao != null) {

            //anular a credencial de assinatura no documento que foi excluído
            $objAtividadeDTO = new AtividadeDTO();
            $objAtividadeDTO->setNumIdTarefa(TarefaRN::$TI_CONCESSAO_CREDENCIAL_ASSINATURA_ANULADA);
            $objAtividadeDTO->setNumIdAtividade($numIdAtividade);
            $objAtividadeBD = new AtividadeBD(BancoSEI::getInstance());
            $objAtividadeBD->alterar($objAtividadeDTO);

            $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
            $objAtributoAndamentoDTO->setStrNome('USUARIO_ANULACAO');
            $objAtributoAndamentoDTO->setStrValor($objAtributoAndamentoDTOExclusao->getStrSiglaUsuarioOrigemAtividade() . '¥' . $objAtributoAndamentoDTOExclusao->getStrNomeUsuarioOrigemAtividade());
            $objAtributoAndamentoDTO->setStrIdOrigem($objAtributoAndamentoDTOExclusao->getNumIdUsuarioOrigemAtividade());
            $objAtributoAndamentoDTO->setNumIdAtividade($numIdAtividade);
            $objAtributoAndamentoRN->cadastrarRN1363($objAtributoAndamentoDTO);

            $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
            $objAtributoAndamentoDTO->setStrNome('DATA_HORA');
            $objAtributoAndamentoDTO->setStrValor($objAtributoAndamentoDTOExclusao->getDthAberturaAtividade());
            $objAtributoAndamentoDTO->setStrIdOrigem($objAtributoAndamentoDTOExclusao->getNumIdAtividade()); //id do andamento que causou a anulação
            $objAtributoAndamentoDTO->setNumIdAtividade($numIdAtividade);
            $objAtributoAndamentoRN->cadastrarRN1363($objAtributoAndamentoDTO);
          }
        }
      }

      unset($rs1);

      InfraDebug::getInstance()->setBolDebugInfra(true);


      $objAtividadeRN = new AtividadeRN();
      $objAtributoAndamentoRN = new AtributoAndamentoRN();

      //busca atividades de concessão de credencial para assinatura onde o usuário não tem credencial no processo
      $sql = 'select atividade.id_atividade, protocolo.protocolo_formatado,' . BancoSEI::getInstance()->formatarSelecaoDbl('atividade', 'id_protocolo', null) . ',atividade.id_usuario,atividade.id_unidade
        from atividade, protocolo, acesso
        where atividade.id_tarefa=' . TarefaRN::$TI_CONCESSAO_CREDENCIAL_ASSINATURA . '
        and not exists (select acesso.id_protocolo from acesso where acesso.id_protocolo=atividade.id_protocolo and acesso.id_usuario=atividade.id_usuario and acesso.id_unidade=atividade.id_unidade and acesso.sta_tipo='.BancoSEI::getInstance()->formatarGravacaoStr(AcessoRN::$TA_CREDENCIAL_PROCESSO).')
        and atividade.id_protocolo=protocolo.id_protocolo
        and protocolo.sta_nivel_acesso_global=' . BancoSEI::getInstance()->formatarGravacaoStr(ProtocoloRN::$NA_SIGILOSO) . '
        and acesso.id_protocolo=atividade.id_protocolo
        and acesso.id_unidade=atividade.id_unidade
        and acesso.id_usuario=atividade.id_usuario
        and acesso.sta_tipo=' . BancoSEI::getInstance()->formatarGravacaoStr(AcessoRN::$TA_CREDENCIAL_ASSINATURA_PROCESSO);

      $rs1 = BancoSEI::getInstance()->consultarSql($sql);

      InfraDebug::getInstance()->setBolDebugInfra(false);

      if (count($rs1)) {

        foreach ($rs1 as $item) {

          $numIdAtividade = $item['id_atividade'];
          $dblIdProtocolo = $item['id_protocolo'];
          $numIdUsuario = $item['id_usuario'];
          $numIdUnidade = $item['id_unidade'];

          //verificando se o usuario/unidade possui outras credenciais de assinatura em documentos existentes do processo

          //busca identificador da última atividade do usuário no processo
          $rs2 = BancoSEI::getInstance()->consultarSql('select max(id_atividade) as ultima
            from atividade
            where id_usuario=' . $numIdUsuario . '
            and id_unidade=' . $numIdUnidade . '
            and id_protocolo=' . $dblIdProtocolo);

          if ($rs2[0]['ultima']) {

            $objAtividadeDTOUltima = new AtividadeDTO();
            $objAtividadeDTOUltima->retNumIdAtividade();
            $objAtividadeDTOUltima->retDthAbertura();
            $objAtividadeDTOUltima->retDthConclusao();
            $objAtividadeDTOUltima->retNumIdUsuario();
            $objAtividadeDTOUltima->retStrSiglaUsuario();
            $objAtividadeDTOUltima->retStrNomeUsuario();
            $objAtividadeDTOUltima->retNumIdUnidade();
            $objAtividadeDTOUltima->retNumIdTarefa();
            $objAtividadeDTOUltima->retStrNomeTarefa();
            $objAtividadeDTOUltima->setNumIdAtividade($rs2[0]['ultima']);

            $objAtividadeDTOUltima = $objAtividadeRN->consultarRN0033($objAtividadeDTOUltima);

            //se a última ação do usuário foi renúnciar ao processo mas ele continua aberto no Controle de Processos
            if ($objAtividadeDTOUltima->getNumIdTarefa() == TarefaRN::$TI_PROCESSO_RENUNCIA_CREDENCIAL && $objAtividadeDTOUltima->getDthConclusao() == null) {

              InfraDebug::getInstance()->gravar($item['protocolo_formatado'].', credencial de assinatura '.$objAtividadeDTOUltima->getNumIdAtividade());

              //finaliza pendencia para sumir do controle de processos
              BancoSEI::getInstance()->executarSql('update atividade
                set dth_conclusao=' . BancoSEI::getInstance()->formatarGravacaoDth($objAtividadeDTOUltima->getDthAbertura()) . '
                where id_atividade=' . $objAtividadeDTOUltima->getNumIdAtividade());


              //remove registros de acesso associados com credencial de assinatura para o usuario/unidade/processo
              BancoSEI::getInstance()->executarSql('delete from acesso
                where id_usuario=' . $numIdUsuario . '
                and id_unidade=' . $numIdUnidade . '
                and (id_protocolo=' . $dblIdProtocolo . ' or id_protocolo in (select id_documento from documento where id_procedimento=' . $dblIdProtocolo . '))
                and sta_tipo=' . BancoSEI::getInstance()->formatarGravacaoStr(AcessoRN::$TA_CREDENCIAL_ASSINATURA_PROCESSO));


              //anular a credencial de assinatura
              $objAtividadeDTO = new AtividadeDTO();
              $objAtividadeDTO->setNumIdTarefa(TarefaRN::$TI_CONCESSAO_CREDENCIAL_ASSINATURA_ANULADA);
              $objAtividadeDTO->setNumIdAtividade($numIdAtividade);
              $objAtividadeBD = new AtividadeBD(BancoSEI::getInstance());
              $objAtividadeBD->alterar($objAtividadeDTO);

              $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
              $objAtributoAndamentoDTO->setStrNome('USUARIO_ANULACAO');
              $objAtributoAndamentoDTO->setStrValor($objAtividadeDTOUltima->getStrSiglaUsuario() . '¥' . $objAtividadeDTOUltima->getStrNomeUsuario());
              $objAtributoAndamentoDTO->setStrIdOrigem($objAtividadeDTOUltima->getNumIdUsuario());
              $objAtributoAndamentoDTO->setNumIdAtividade($numIdAtividade);
              $objAtributoAndamentoRN->cadastrarRN1363($objAtributoAndamentoDTO);

              $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
              $objAtributoAndamentoDTO->setStrNome('DATA_HORA');
              $objAtributoAndamentoDTO->setStrValor($objAtividadeDTOUltima->getDthAbertura());
              $objAtributoAndamentoDTO->setStrIdOrigem($objAtividadeDTOUltima->getNumIdAtividade()); //id do andamento que causou a anulação
              $objAtributoAndamentoDTO->setNumIdAtividade($numIdAtividade);
              $objAtributoAndamentoRN->cadastrarRN1363($objAtributoAndamentoDTO);
            }
          }
        }
      }

      unset($rs1);

      InfraDebug::getInstance()->setBolDebugInfra(true);

    }catch(Exception $e){
      throw new InfraException('Erro corrigindo credenciais de assinatura.', $e);
    }
  }

  protected function fixSenhaBcryptControlado(){
    try {
      InfraDebug::getInstance()->gravar('ATUALIZANDO SENHAS DE USUARIOS EXTERNOS...');


      InfraDebug::getInstance()->setBolDebugInfra(false);
      $objUsuarioDTO = new UsuarioDTO();
      $objUsuarioRN = new UsuarioRN();
      $objUsuarioBD = new UsuarioBD(BancoSEI::getInstance());
      $objUsuarioDTO->setBolExclusaoLogica(false);
      $objUsuarioDTO->setStrStaTipo(array(2, 3), InfraDTO::$OPER_IN);
      $objUsuarioDTO->retNumIdUsuario();
      $objUsuarioDTO->retStrSenha();
      $arrObjUsuarioDTO = $objUsuarioRN->listarRN0490($objUsuarioDTO);

      $objInfraMetaBD = new InfraMetaBD(BancoSEI::getInstance());
      $objInfraMetaBD->alterarColuna('usuario','senha',$objInfraMetaBD->tipoTextoFixo(60),'null');

      $bcrypt = new InfraBcrypt();

      $numRegistros = count($arrObjUsuarioDTO);
      $n = 0;
      foreach ($arrObjUsuarioDTO as $objUsuarioDTO) {

        if ((++$n >=100 && $n%100==0) || $n==$numRegistros){
          InfraDebug::getInstance()->gravar('USUARIOS: '.$n.' DE '.$numRegistros);
        }

        $objUsuarioDTO->setStrSenha($bcrypt->hash($objUsuarioDTO->getStrSenha()));
        $objUsuarioBD->alterar($objUsuarioDTO);
      }
      InfraDebug::getInstance()->setBolDebugInfra(true);

    }catch(Exception $e){
      throw new InfraException('Erro migrando senhas de usuários externos.', $e);
    }
  }

  protected function fixUsuariosSemContatoControlado(){
    try {
      InfraDebug::getInstance()->gravar('ATUALIZANDO USUÁRIOS SEM CONTATO...');

      $rs = BancoSEI::getInstance()->consultarSql('select '.
        BancoSEI::getInstance()->formatarSelecaoNum('usuario','id_usuario ','idusuario') .','.
        BancoSEI::getInstance()->formatarSelecaoStr('usuario','sigla','siglausuario') .','.
        BancoSEI::getInstance()->formatarSelecaoStr('usuario','nome','nomeusuario') .','.
        BancoSEI::getInstance()->formatarSelecaoDbl('usuario', 'cpf', 'cpfusuario') .','.
        BancoSEI::getInstance()->formatarSelecaoStr('usuario','sta_tipo','statipousuario') .','.
        BancoSEI::getInstance()->formatarSelecaoStr('orgao','sigla','siglaorgao') .
        ' from usuario, orgao where usuario.id_orgao=orgao.id_orgao and usuario.id_contato is null');

      $objInfraParametro = new InfraParametro(BancoSEI::getInstance());

      foreach($rs as $usuario) {

        $numIdUsuario = BancoSEI::getInstance()->formatarLeituraNum($usuario['idusuario']);
        $strSiglaUsuario = BancoSEI::getInstance()->formatarLeituraStr($usuario['siglausuario']);
        $strNomeUsuario = BancoSEI::getInstance()->formatarLeituraStr($usuario['nomeusuario']);
        $dblCpfUsuario = BancoSEI::getInstance()->formatarLeituraDbl($usuario['cpfusuario']);
        $strStaTipo = BancoSEI::getInstance()->formatarLeituraStr($usuario['statipousuario']);
        $strSiglaOrgao = BancoSEI::getInstance()->formatarLeituraStr($usuario['siglaorgao']);


        if ($strStaTipo == UsuarioRN::$TU_SISTEMA) {

          $numIdTipoContato = $objInfraParametro->getValor('ID_TIPO_CONTATO_SISTEMAS');

        } else if ($strStaTipo == UsuarioRN::$TU_EXTERNO || $strStaTipo == UsuarioRN::$TU_EXTERNO_PENDENTE) {

          if (!$objInfraParametro->isSetValor($strSiglaOrgao . '_ID_TIPO_CONTATO_USUARIOS_EXTERNOS')) {
            $objTipoContatoDTO = new TipoContatoDTO();
            $objTipoContatoDTO->setNumIdTipoContato(null);
            $objTipoContatoDTO->setStrNome('Usuários Externos ' . $strSiglaOrgao);
            $objTipoContatoDTO->setStrDescricao('Usuários Externos ' . $strSiglaOrgao);
            $objTipoContatoDTO->setStrStaAcesso(TipoContatoRN::$TA_CONSULTA_RESUMIDA);
            $objTipoContatoDTO->setStrSinSistema('S');
            $objTipoContatoDTO->setStrSinAtivo('S');

            $objTipoContatoRN = new TipoContatoRN();
            $objTipoContatoDTO = $objTipoContatoRN->cadastrarRN0334($objTipoContatoDTO);

            $objRelUnidadeTipoContatoDTO = new RelUnidadeTipoContatoDTO();
            $objRelUnidadeTipoContatoDTO->setNumIdTipoContato($objTipoContatoDTO->getNumIdTipoContato());
            $objRelUnidadeTipoContatoDTO->setNumIdUnidade($objInfraParametro->getValor('ID_UNIDADE_TESTE'));
            $objRelUnidadeTipoContatoDTO->setStrStaAcesso(TipoContatoRN::$TA_ALTERACAO);

            $objRelUnidadeTipoContatoRN = new RelUnidadeTipoContatoRN();
            $objRelUnidadeTipoContatoRN->cadastrarRN0545($objRelUnidadeTipoContatoDTO);

            $objInfraParametro->setValor($strSiglaOrgao . '_ID_TIPO_CONTATO_USUARIOS_EXTERNOS', $objTipoContatoDTO->getNumIdTipoContato());
          }

          $numIdTipoContato = $objInfraParametro->getValor($strSiglaOrgao . '_ID_TIPO_CONTATO_USUARIOS_EXTERNOS');

        } else {
          if (!$objInfraParametro->isSetValor($strSiglaOrgao . '_ID_TIPO_CONTATO_USUARIOS')) {
            $objTipoContatoDTO = new TipoContatoDTO();
            $objTipoContatoDTO->setNumIdTipoContato(null);
            $objTipoContatoDTO->setStrNome('Usuários ' . $strSiglaOrgao);
            $objTipoContatoDTO->setStrDescricao('Usuários ' . $strSiglaOrgao);
            $objTipoContatoDTO->setStrStaAcesso(TipoContatoRN::$TA_CONSULTA_RESUMIDA);
            $objTipoContatoDTO->setStrSinSistema('S');
            $objTipoContatoDTO->setStrSinAtivo('S');

            $objTipoContatoRN = new TipoContatoRN();
            $objTipoContatoDTO = $objTipoContatoRN->cadastrarRN0334($objTipoContatoDTO);

            $objRelUnidadeTipoContatoDTO = new RelUnidadeTipoContatoDTO();
            $objRelUnidadeTipoContatoDTO->setNumIdTipoContato($objTipoContatoDTO->getNumIdTipoContato());
            $objRelUnidadeTipoContatoDTO->setNumIdUnidade($objInfraParametro->getValor('ID_UNIDADE_TESTE'));
            $objRelUnidadeTipoContatoDTO->setStrStaAcesso(TipoContatoRN::$TA_ALTERACAO);

            $objRelUnidadeTipoContatoRN = new RelUnidadeTipoContatoRN();
            $objRelUnidadeTipoContatoRN->cadastrarRN0545($objRelUnidadeTipoContatoDTO);

            $objInfraParametro->setValor($strSiglaOrgao . '_ID_TIPO_CONTATO_USUARIOS', $objTipoContatoDTO->getNumIdTipoContato());
          }

          $numIdTipoContato = $objInfraParametro->getValor($strSiglaOrgao . '_ID_TIPO_CONTATO_USUARIOS');
        }

        $objContatoDTO = new ContatoDTO();
        $objContatoDTO->retNumIdContato();
        $objContatoDTO->setStrSigla($strSiglaUsuario);
        $objContatoDTO->setStrNome($strNomeUsuario);
        $objContatoDTO->setNumIdTipoContato($numIdTipoContato);

        $objContatoRN = new ContatoRN();
        $objContatoDTO = $objContatoRN->consultarRN0324($objContatoDTO);

        if ($objContatoDTO == null) {

          $objContatoDTO = new ContatoDTO();

          $objContatoDTO->setNumIdContato(null);
          $objContatoDTO->setNumIdTipoContato($numIdTipoContato);
          $objContatoDTO->setNumIdContatoAssociado(null);
          $objContatoDTO->setStrStaNatureza(ContatoRN::$TN_PESSOA_FISICA);
          $objContatoDTO->setDblCnpj(null);
          $objContatoDTO->setNumIdCargo(null);
          $objContatoDTO->setStrSigla($strSiglaUsuario);
          $objContatoDTO->setStrNome($strNomeUsuario);
          $objContatoDTO->setDtaNascimento(null);
          $objContatoDTO->setStrStaGenero(null);
          $objContatoDTO->setDblCpf($dblCpfUsuario);
          $objContatoDTO->setDblRg(null);
          $objContatoDTO->setStrOrgaoExpedidor(null);
          $objContatoDTO->setStrMatricula(null);
          $objContatoDTO->setStrMatriculaOab(null);
          $objContatoDTO->setStrEndereco(null);
          $objContatoDTO->setStrComplemento(null);

          if ($strStaTipo == UsuarioRN::$TU_EXTERNO || $strStaTipo == UsuarioRN::$TU_EXTERNO_PENDENTE) {
            $objContatoDTO->setStrEmail($strSiglaUsuario);
          } else {
            $objContatoDTO->setStrEmail(null);
          }

          $objContatoDTO->setStrSitioInternet(null);
          $objContatoDTO->setStrTelefoneFixo(null);
          $objContatoDTO->setStrTelefoneCelular(null);
          $objContatoDTO->setStrBairro(null);
          $objContatoDTO->setNumIdUf(null);
          $objContatoDTO->setNumIdCidade(null);
          $objContatoDTO->setNumIdPais(null);
          $objContatoDTO->setStrCep(null);
          $objContatoDTO->setStrObservacao(null);
          $objContatoDTO->setStrSinEnderecoAssociado('N');
          $objContatoDTO->setStrSinAtivo('S');
          $objContatoDTO->setStrStaOperacao('REPLICACAO');

          $objContatoDTO = $objContatoRN->cadastrarRN0322($objContatoDTO);
        }

        BancoSEI::getInstance()->executarSql('update usuario set id_contato='.$objContatoDTO->getNumIdContato().' where id_usuario='.$numIdUsuario);
      }

    }catch(Exception $e){
      throw new InfraException('Erro tratando usuários sem contato.', $e);
    }
  }

  protected function fixSinalizadorSistemaControlado(){
    try {
      InfraDebug::getInstance()->gravar('ATUALIZANDO SINALIZADOR DE SISTEMA PARA TIPOS DE CONTATO...');

      $objInfraParametro = new InfraParametro(BancoSEI::getInstance());

      $objTipoContatoRN = new TipoContatoRN();

      $numIdTipoContato = $objInfraParametro->getValor('ID_TIPO_CONTATO_SISTEMAS', false);
      if (!InfraString::isBolVazia($numIdTipoContato)) {
        $objTipoContatoDTO = new TipoContatoDTO();
        $objTipoContatoDTO->setBolExclusaoLogica(false);
        $objTipoContatoDTO->setNumIdTipoContato($numIdTipoContato);
        if ($objTipoContatoRN->contarRN0353($objTipoContatoDTO)) {
          BancoSEI::getInstance()->executarSql('update tipo_contato set sin_sistema=\'S\' where id_tipo_contato=' . $numIdTipoContato);
        }
      }

      $rs = BancoSEI::getInstance()->consultarSql('select sigla from orgao order by sigla');

      $arrChavesTiposContatos = array('_ID_TIPO_CONTATO_USUARIOS','_ID_TIPO_CONTATO_UNIDADES','_ID_TIPO_CONTATO_USUARIOS_EXTERNOS');

      foreach($rs as $orgao){
        foreach($arrChavesTiposContatos as $strChaveTipoContato) {
          $numIdTipoContato = $objInfraParametro->getValor(BancoSEI::getInstance()->formatarLeituraStr($orgao['sigla']) . $strChaveTipoContato, false);
          if (!InfraString::isBolVazia($numIdTipoContato)) {
            $objTipoContatoDTO = new TipoContatoDTO();
            $objTipoContatoDTO->setBolExclusaoLogica(false);
            $objTipoContatoDTO->setNumIdTipoContato($numIdTipoContato);
            if ($objTipoContatoRN->contarRN0353($objTipoContatoDTO)) {
              BancoSEI::getInstance()->executarSql('update tipo_contato set sin_sistema=\'S\' where id_tipo_contato=' . $numIdTipoContato);
            }
          }
        }
      }

    }catch(Exception $e){
      throw new InfraException('Erro sinalizando tipos de contato de sistemas.', $e);
    }
  }

  protected function fixSinalizadorPesquisaControlado(){
    try {
      InfraDebug::getInstance()->gravar('ATUALIZANDO SINALIZADOR DE PESQUISA PARA TIPOS DE CONTATO...');

      $objInfraParametro = new InfraParametro(BancoSEI::getInstance());

      $numIdTipoContato = $objInfraParametro->getValor('ID_TIPO_CONTATO_SISTEMAS', false);
      if (!InfraString::isBolVazia($numIdTipoContato)) {
        BancoSEI::getInstance()->executarSql('update tipo_contato set sta_acesso=\''.TipoContatoRN::$TA_NENHUM.'\' where id_tipo_contato=' . $numIdTipoContato);
      }

      $numIdTipoContato = $objInfraParametro->getValor('ID_TIPO_CONTATO_TEMPORARIO', false);
      if (!InfraString::isBolVazia($numIdTipoContato)) {
        BancoSEI::getInstance()->executarSql('update tipo_contato set sta_acesso=\''.TipoContatoRN::$TA_CONSULTA_RESUMIDA.'\' where id_tipo_contato=' . $numIdTipoContato);
      }

      $numIdTipoContato = $objInfraParametro->getValor('ID_TIPO_CONTATO_OUVIDORIA', false);
      if (!InfraString::isBolVazia($numIdTipoContato)) {
        BancoSEI::getInstance()->executarSql('update tipo_contato set sta_acesso=\''.TipoContatoRN::$TA_NENHUM.'\' where id_tipo_contato=' . $numIdTipoContato);
      }

      $rs = BancoSEI::getInstance()->consultarSql('select sigla from orgao order by sigla');

      foreach($rs as $orgao){

        $strSiglaOrgao = BancoSEI::getInstance()->formatarLeituraStr($orgao['sigla']);

        $numIdTipoContato = $objInfraParametro->getValor($strSiglaOrgao . '_ID_TIPO_CONTATO_USUARIOS', false);
        if (!InfraString::isBolVazia($numIdTipoContato)) {
          BancoSEI::getInstance()->executarSql('update tipo_contato set sta_acesso=\''.TipoContatoRN::$TA_CONSULTA_RESUMIDA.'\' where id_tipo_contato=' . $numIdTipoContato);
        }

        $numIdTipoContato = $objInfraParametro->getValor($strSiglaOrgao . '_ID_TIPO_CONTATO_USUARIOS_EXTERNOS', false);
        if (!InfraString::isBolVazia($numIdTipoContato)) {
          BancoSEI::getInstance()->executarSql('update tipo_contato set sta_acesso=\''.TipoContatoRN::$TA_CONSULTA_RESUMIDA.'\' where id_tipo_contato=' . $numIdTipoContato);
        }

        $numIdTipoContato = $objInfraParametro->getValor($strSiglaOrgao . '_ID_TIPO_CONTATO_UNIDADES', false);
        if (!InfraString::isBolVazia($numIdTipoContato)) {
          BancoSEI::getInstance()->executarSql('update tipo_contato set sta_acesso=\''.TipoContatoRN::$TA_CONSULTA_COMPLETA.'\' where id_tipo_contato=' . $numIdTipoContato);
        }
      }

    }catch(Exception $e){
      throw new InfraException('Erro sinalizando tipo de pesquisa de contatos.', $e);
    }
  }

  protected function fixAtualizarContatosUnidadesControlado(){
    try {
      InfraDebug::getInstance()->gravar('ATUALIZANDO CONTATOS DE UNIDADES...');

      $rs = BancoSEI::getInstance()->consultarSql('select '.
        BancoSEI::getInstance()->formatarSelecaoNum('unidade', 'id_contato', 'idcontatounidade').','.
        BancoSEI::getInstance()->formatarSelecaoStr('unidade', 'endereco', 'enderecounidade').','.
        BancoSEI::getInstance()->formatarSelecaoStr('unidade', 'complemento', 'complementounidade').','.
        BancoSEI::getInstance()->formatarSelecaoStr('unidade', 'bairro', 'bairrounidade').','.
        BancoSEI::getInstance()->formatarSelecaoNum('unidade', 'id_uf', 'idufunidade').','.
        BancoSEI::getInstance()->formatarSelecaoNum('unidade', 'id_cidade', 'idcidadeunidade').','.
        BancoSEI::getInstance()->formatarSelecaoStr('unidade', 'cep', 'cepunidade').','.
        BancoSEI::getInstance()->formatarSelecaoStr('unidade', 'telefone', 'telefoneunidade').','.
        BancoSEI::getInstance()->formatarSelecaoStr('unidade', 'sitio_internet', 'sitiointernetunidade').','.
        BancoSEI::getInstance()->formatarSelecaoStr('unidade', 'observacao', 'observacaounidade').
        ' from unidade');

      $objPaisDTO = new PaisDTO();
      $objPaisDTO->retNumIdPais();
      $objPaisDTO->retStrNome();

      $objPaisRN = new PaisRN();
      $arrObjPaisDTO = $objPaisRN->listar($objPaisDTO);

      $numIdPaisBrasil = null;
      foreach($arrObjPaisDTO as $objPaisDTO){
        if (InfraString::transformarCaixaAlta($objPaisDTO->getStrNome())=='BRASIL'){
          $numIdPaisBrasil = $objPaisDTO->getNumIdPais();
          break;
        }
      }

      InfraDebug::getInstance()->setBolDebugInfra(false);
      foreach($rs as $unidade){
        BancoSEI::getInstance()->executarSql('update contato set '.
          'endereco='.BancoSEI::getInstance()->formatarGravacaoStr(BancoSEI::getInstance()->formatarLeituraStr($unidade['enderecounidade'])).','.
          'complemento='.BancoSEI::getInstance()->formatarGravacaoStr(BancoSEI::getInstance()->formatarLeituraStr($unidade['complementounidade'])).','.
          'bairro='.BancoSEI::getInstance()->formatarGravacaoStr(BancoSEI::getInstance()->formatarLeituraStr($unidade['bairrounidade'])).','.
          'id_uf='.BancoSEI::getInstance()->formatarGravacaoNum(BancoSEI::getInstance()->formatarLeituraNum($unidade['idufunidade'])).','.
          'id_cidade='.BancoSEI::getInstance()->formatarGravacaoNum(BancoSEI::getInstance()->formatarLeituraNum($unidade['idcidadeunidade'])).','.
          'id_pais='.BancoSEI::getInstance()->formatarGravacaoNum($numIdPaisBrasil).','.
          'cep='.BancoSEI::getInstance()->formatarGravacaoStr(BancoSEI::getInstance()->formatarLeituraStr($unidade['cepunidade'])).','.
          'telefone_fixo='.BancoSEI::getInstance()->formatarGravacaoStr(BancoSEI::getInstance()->formatarLeituraStr($unidade['telefoneunidade'])).','.
          'sitio_internet='.BancoSEI::getInstance()->formatarGravacaoStr(BancoSEI::getInstance()->formatarLeituraStr($unidade['sitiointernetunidade'])).','.
          'observacao='.BancoSEI::getInstance()->formatarGravacaoStr(BancoSEI::getInstance()->formatarLeituraStr($unidade['observacaounidade'])).
          ' where id_contato='.BancoSEI::getInstance()->formatarLeituraNum($unidade['idcontatounidade']));
      }
      InfraDebug::getInstance()->setBolDebugInfra(true);

    }catch(Exception $e){
      throw new InfraException('Erro atualizando contatos de unidades.', $e);
    }
  }

  protected function fixCriarOrgaosContatosControlado(){
    try {
      InfraDebug::getInstance()->gravar('CRIANDO CONTATOS PARA ORGAOS...');

      $rs = BancoSEI::getInstance()->consultarSql('select '.
        BancoSEI::getInstance()->formatarSelecaoNum('orgao', 'id_orgao', 'idorgao').','.
        BancoSEI::getInstance()->formatarSelecaoStr('orgao', 'sigla', 'siglaorgao').','.
        BancoSEI::getInstance()->formatarSelecaoStr('orgao', 'descricao', 'descricaoorgao').','.
        BancoSEI::getInstance()->formatarSelecaoStr('orgao', 'endereco', 'enderecoorgao').','.
        BancoSEI::getInstance()->formatarSelecaoStr('orgao', 'complemento', 'complementoorgao').','.
        BancoSEI::getInstance()->formatarSelecaoStr('orgao', 'bairro', 'bairroorgao').','.
        BancoSEI::getInstance()->formatarSelecaoNum('uf', 'id_uf', 'iduforgao').','.
        BancoSEI::getInstance()->formatarSelecaoNum('cidade', 'id_cidade', 'idcidadeorgao').','.
        BancoSEI::getInstance()->formatarSelecaoStr('orgao', 'cep', 'ceporgao').','.
        BancoSEI::getInstance()->formatarSelecaoStr('orgao', 'telefone', 'telefoneorgao').','.
        BancoSEI::getInstance()->formatarSelecaoStr('orgao', 'email', 'emailorgao').','.
        BancoSEI::getInstance()->formatarSelecaoStr('orgao', 'sitio_internet', 'sitiointernetorgao').
        ' from orgao left join (cidade left join uf on cidade.id_uf=uf.id_uf) on orgao.id_cidade=cidade.id_cidade');

      $objInfraParametro = new InfraParametro(BancoSEI::getInstance());

      $objTipoContatoDTO = new TipoContatoDTO();
      $objTipoContatoDTO->setNumIdTipoContato(null);
      $objTipoContatoDTO->setStrNome('Órgãos');
      $objTipoContatoDTO->setStrDescricao('Órgãos');
      $objTipoContatoDTO->setStrStaAcesso(TipoContatoRN::$TA_CONSULTA_COMPLETA);
      $objTipoContatoDTO->setStrSinSistema('S');
      $objTipoContatoDTO->setStrSinAtivo('S');

      $objTipoContatoRN = new TipoContatoRN();
      $objTipoContatoDTO = $objTipoContatoRN->cadastrarRN0334($objTipoContatoDTO);

      $numIdTipoContato = $objTipoContatoDTO->getNumIdTipoContato();

      $objRelUnidadeTipoContatoDTO = new RelUnidadeTipoContatoDTO();
      $objRelUnidadeTipoContatoDTO->setNumIdTipoContato($objTipoContatoDTO->getNumIdTipoContato());
      $objRelUnidadeTipoContatoDTO->setNumIdUnidade($objInfraParametro->getValor('ID_UNIDADE_TESTE'));
      $objRelUnidadeTipoContatoDTO->setStrStaAcesso(TipoContatoRN::$TA_ALTERACAO);

      $objRelUnidadeTipoContatoRN = new RelUnidadeTipoContatoRN();
      $objRelUnidadeTipoContatoRN->cadastrarRN0545($objRelUnidadeTipoContatoDTO);

      BancoSEI::getInstance()->executarSql('insert into infra_parametro (nome,valor) values (\'ID_TIPO_CONTATO_ORGAOS\',\''.$numIdTipoContato.'\')');

      $objPaisDTO = new PaisDTO();
      $objPaisDTO->retNumIdPais();
      $objPaisDTO->retStrNome();

      $objPaisRN = new PaisRN();
      $arrObjPaisDTO = $objPaisRN->listar($objPaisDTO);

      $numIdPaisBrasil = null;
      foreach($arrObjPaisDTO as $objPaisDTO){
        if (InfraString::transformarCaixaAlta($objPaisDTO->getStrNome())=='BRASIL'){
          $numIdPaisBrasil = $objPaisDTO->getNumIdPais();
          break;
        }
      }

      $objContatoRN = new ContatoRN();

      foreach($rs as $orgao) {

        $objContatoDTO = new ContatoDTO();

        $objContatoDTO->setNumIdContato(null);
        $objContatoDTO->setNumIdTipoContato($numIdTipoContato);
        $objContatoDTO->setNumIdContatoAssociado(null);
        $objContatoDTO->setStrStaNatureza(ContatoRN::$TN_PESSOA_JURIDICA);
        $objContatoDTO->setDblCnpj(null);
        $objContatoDTO->setNumIdCargo(null);
        $objContatoDTO->setStrSigla(BancoSEI::getInstance()->formatarLeituraStr($orgao['siglaorgao']));
        $objContatoDTO->setStrNome(BancoSEI::getInstance()->formatarLeituraStr($orgao['descricaoorgao']));
        $objContatoDTO->setDtaNascimento(null);
        $objContatoDTO->setStrStaGenero(null);
        $objContatoDTO->setDblCpf(null);
        $objContatoDTO->setDblRg(null);
        $objContatoDTO->setStrOrgaoExpedidor(null);
        $objContatoDTO->setStrMatricula(null);
        $objContatoDTO->setStrMatriculaOab(null);
        $objContatoDTO->setStrEndereco(BancoSEI::getInstance()->formatarLeituraStr($orgao['enderecoorgao']));
        $objContatoDTO->setStrComplemento(BancoSEI::getInstance()->formatarLeituraStr($orgao['complementoorgao']));
        $objContatoDTO->setStrEmail(BancoSEI::getInstance()->formatarLeituraStr($orgao['emailorgao']));
        $objContatoDTO->setStrSitioInternet(BancoSEI::getInstance()->formatarLeituraStr($orgao['sitiointernetorgao']));
        $objContatoDTO->setStrTelefoneFixo(BancoSEI::getInstance()->formatarLeituraStr($orgao['telefoneorgao']));
        $objContatoDTO->setStrTelefoneCelular(null);
        $objContatoDTO->setStrBairro(BancoSEI::getInstance()->formatarLeituraStr($orgao['bairroorgao']));
        $objContatoDTO->setNumIdUf(BancoSEI::getInstance()->formatarLeituraNum($orgao['iduforgao']));
        $objContatoDTO->setNumIdCidade(BancoSEI::getInstance()->formatarLeituraNum($orgao['idcidadeorgao']));
        $objContatoDTO->setNumIdPais($numIdPaisBrasil);
        $objContatoDTO->setStrCep(BancoSEI::getInstance()->formatarLeituraStr($orgao['ceporgao']));
        $objContatoDTO->setStrObservacao(null);
        $objContatoDTO->setStrSinEnderecoAssociado('N');
        $objContatoDTO->setStrSinAtivo('S');
        $objContatoDTO->setStrStaOperacao('REPLICACAO');

        $objContatoDTO = $objContatoRN->cadastrarRN0322($objContatoDTO);

        BancoSEI::getInstance()->executarSql('update orgao set id_contato='.$objContatoDTO->getNumIdContato().' where id_orgao='.BancoSEI::getInstance()->formatarLeituraNum($orgao['idorgao']));
      }

    }catch(Exception $e){
      throw new InfraException('Erro criando contatos para órgãos.', $e);
    }
  }

  public function fixContatoCidadeUfPais(){
    try {
      InfraDebug::getInstance()->gravar('ATUALIZANDO CIDADE, UF E PAIS EM CONTATOS...');

      $rs = BancoSEI::getInstance()->consultarSql('select '.
        BancoSEI::getInstance()->formatarSelecaoNum('contato', 'id_contato', 'idcontato').','.
        BancoSEI::getInstance()->formatarSelecaoStr('contato', 'nome_cidade', 'nomecidadecontato').','.
        BancoSEI::getInstance()->formatarSelecaoStr('contato', 'sigla_estado', 'siglaestadocontato').','.
        BancoSEI::getInstance()->formatarSelecaoStr('contato', 'nome_pais', 'nomepaiscontato').
        ' from contato');

      $objPaisDTO = new PaisDTO();
      $objPaisDTO->retNumIdPais();
      $objPaisDTO->retStrNome();

      $objPaisRN = new PaisRN();
      $arrObjPaisDTO = $objPaisRN->listar($objPaisDTO);

      $numIdPaisBrasil = null;
      foreach($arrObjPaisDTO as $objPaisDTO){
        $objPaisDTO->setStrNome(InfraString::transformarCaixaAlta($objPaisDTO->getStrNome()));

        if ($objPaisDTO->getStrNome()=='BRASIL'){
          $numIdPaisBrasil = $objPaisDTO->getNumIdPais();
        }
      }

      $arrObjPaisDTO = InfraArray::indexarArrInfraDTO($arrObjPaisDTO,'Nome');

      $objUfDTO = new UfDTO();
      $objUfDTO->retNumIdPais();
      $objUfDTO->retNumIdUf();
      $objUfDTO->retStrSigla();

      $objUfRN = new UfRN();
      $arrObjUfDTO = $objUfRN->listarRN0401($objUfDTO);

      foreach($arrObjUfDTO as $objUfDTO){
        $objUfDTO->setStrSigla(InfraString::transformarCaixaAlta($objUfDTO->getStrSigla()));
      }

      $arrObjUfDTO = InfraArray::indexarArrInfraDTO($arrObjUfDTO,'Sigla',true);

      $objCidadeDTO = new CidadeDTO();
      $objCidadeDTO->retNumIdPais();
      $objCidadeDTO->retNumIdUf();
      $objCidadeDTO->retNumIdCidade();
      $objCidadeDTO->retStrNome();

      $objCidadeRN = new CidadeRN();
      $arrObjCidadeDTO = $objCidadeRN->listarRN0410($objCidadeDTO);

      foreach($arrObjCidadeDTO as $objCidadeDTO){
        $objCidadeDTO->setStrNome(InfraString::transformarCaixaAlta($objCidadeDTO->getStrNome()));
      }

      $arrObjCidadeDTO = InfraArray::indexarArrInfraDTO($arrObjCidadeDTO,'Nome',true);

      $numRegistros = count($rs);
      $n = 0;
      InfraDebug::getInstance()->setBolDebugInfra(false);
      foreach($rs as $contato) {

        if ((++$n >=1000 && $n%1000==0) || $n==$numRegistros){
          InfraDebug::getInstance()->gravar('CONTATOS: '.$n.' DE '.$numRegistros);
        }

        $numIdContato = BancoSEI::getInstance()->formatarLeituraNum($contato['idcontato']);
        $strNomeCidade = trim(InfraString::transformarCaixaAlta(BancoSEI::getInstance()->formatarLeituraStr($contato['nomecidadecontato'])));
        $strSiglaEstado = trim(InfraString::transformarCaixaAlta(BancoSEI::getInstance()->formatarLeituraStr($contato['siglaestadocontato'])));
        $strNomePais = trim(InfraString::transformarCaixaAlta(BancoSEI::getInstance()->formatarLeituraStr($contato['nomepaiscontato'])));

        $numIdPais = $numIdPaisBrasil;
        if ($strNomePais!=null && isset($arrObjPaisDTO[$strNomePais])){
          $numIdPais = $arrObjPaisDTO[$strNomePais]->getNumIdPais();
        }

        $numIdUf = null;
        if ($strSiglaEstado!=null && isset($arrObjUfDTO[$strSiglaEstado])){
          foreach($arrObjUfDTO[$strSiglaEstado] as $objUfDTO){
            if ($objUfDTO->getNumIdPais()==$numIdPais){
              $numIdUf = $objUfDTO->getNumIdUf();
              break;
            }
          }
        }

        $numIdCidade = null;
        if ($strNomeCidade!=null && isset($arrObjCidadeDTO[$strNomeCidade])){
          foreach($arrObjCidadeDTO[$strNomeCidade] as $objCidadeDTO){
            if ($objCidadeDTO->getNumIdPais()==$numIdPais && ($numIdUf==null || $numIdUf==$objCidadeDTO->getNumIdUf())){
              $numIdCidade = $objCidadeDTO->getNumIdCidade();
              break;
            }
          }
        }

        BancoSEI::getInstance()->executarSql('update contato set id_pais='.BancoSEI::getInstance()->formatarGravacaoNum($numIdPais).', id_uf='.BancoSEI::getInstance()->formatarGravacaoNum($numIdUf).',id_cidade='.BancoSEI::getInstance()->formatarGravacaoNum($numIdCidade).' where id_contato='.BancoSEI::getInstance()->formatarGravacaoNum($numIdContato));
      }
      InfraDebug::getInstance()->setBolDebugInfra(true);

    }catch(Exception $e){
      throw new InfraException('Erro atualizando cidade, uf e pais em contatos.', $e);
    }
  }

  protected function fixAssociarUsuariosOrgaosControlado(){
    try {
      InfraDebug::getInstance()->gravar('ASSOCIANDO CONTATOS DE ORGAOS E USUARIOS...');

      $objUsuarioDTO = new UsuarioDTO();
      $objUsuarioDTO->setBolExclusaoLogica(false);
      $objUsuarioDTO->retNumIdOrgao();
      $objUsuarioDTO->retNumIdContato();
      $objUsuarioDTO->setStrStaTipo(UsuarioRN::$TU_SIP);

      $objUsuarioRN = new UsuarioRN();
      $arrObjUsuarioDTO = $objUsuarioRN->listarRN0490($objUsuarioDTO);

      $objOrgaoDTO = new OrgaoDTO();
      $objOrgaoDTO->setBolExclusaoLogica(false);
      $objOrgaoDTO->retNumIdOrgao();
      $objOrgaoDTO->retNumIdContato();

      $objOrgaoRN = new OrgaoRN();
      $arrObjOrgaoDTO = InfraArray::indexarArrInfraDTO($objOrgaoRN->listarRN1353($objOrgaoDTO),'IdOrgao');

      $objContatoBD = new ContatoBD(BancoSEI::getInstance());

      $numRegistros = count($arrObjUsuarioDTO);
      $n = 0;

      InfraDebug::getInstance()->setBolDebugInfra(false);
      foreach($arrObjUsuarioDTO as $objUsuarioDTO) {

        if ((++$n >=500 && $n%500==0) || $n==$numRegistros){
          InfraDebug::getInstance()->gravar('USUARIOS: '.$n.' DE '.$numRegistros);
        }

        $dto = new ContatoDTO();
        $dto->setNumIdContatoAssociado($arrObjOrgaoDTO[$objUsuarioDTO->getNumIdOrgao()]->getNumIdContato());
        $dto->setStrSinEnderecoAssociado('S');
        $dto->setNumIdContato($objUsuarioDTO->getNumIdContato());
        $objContatoBD->alterar($dto);
      }
      InfraDebug::getInstance()->setBolDebugInfra(true);

    }catch(Exception $e){
      throw new InfraException('Erro associando contatos de usuários com órgãos.', $e);
    }
  }

  protected function fixAssociarUnidadesOrgaosControlado(){
    try {
      InfraDebug::getInstance()->gravar('ASSOCIANDO CONTATOS DE ORGAOS E UNIDADES...');

      $objUnidadeDTO = new UnidadeDTO();
      $objUnidadeDTO->setBolExclusaoLogica(false);
      $objUnidadeDTO->retNumIdOrgao();
      $objUnidadeDTO->retNumIdContato();

      $objUnidadeRN = new UnidadeRN();
      $arrObjUnidadeDTO = $objUnidadeRN->listarRN0127($objUnidadeDTO);

      $objOrgaoDTO = new OrgaoDTO();
      $objOrgaoDTO->setBolExclusaoLogica(false);
      $objOrgaoDTO->retNumIdOrgao();
      $objOrgaoDTO->retNumIdContato();

      $objOrgaoRN = new OrgaoRN();
      $arrObjOrgaoDTO = InfraArray::indexarArrInfraDTO($objOrgaoRN->listarRN1353($objOrgaoDTO),'IdOrgao');

      $objContatoBD = new ContatoBD(BancoSEI::getInstance());

      $numRegistros = count($arrObjUnidadeDTO);
      $n = 0;

      InfraDebug::getInstance()->setBolDebugInfra(false);
      foreach($arrObjUnidadeDTO as $objUnidadeDTO) {

        if ((++$n >=100 && $n%100==0) || $n==$numRegistros){
          InfraDebug::getInstance()->gravar('UNIDADES: '.$n.' DE '.$numRegistros);
        }

        $dto = new ContatoDTO();
        $dto->setNumIdContatoAssociado($arrObjOrgaoDTO[$objUnidadeDTO->getNumIdOrgao()]->getNumIdContato());
        $dto->setStrSinEnderecoAssociado('N');
        $dto->setNumIdContato($objUnidadeDTO->getNumIdContato());
        $objContatoBD->alterar($dto);
      }
      InfraDebug::getInstance()->setBolDebugInfra(true);

    }catch(Exception $e){
      throw new InfraException('Erro associando contatos de unidades com órgãos.', $e);
    }
  }

  public function fixControleInterno(){
    try {

      InfraDebug::getInstance()->gravar('PROCESSANDO DADOS DE CONTROLE INTERNO...');

      //obtem processos restritos com acesso automatico
      $objAtividadeDTO = new AtividadeDTO();
      $objAtividadeDTO->setDistinct(true);
      $objAtividadeDTO->retDblIdProtocolo();
      $objAtividadeDTO->setStrStaNivelAcessoGlobalProtocolo(ProtocoloRN::$NA_RESTRITO);
      $objAtividadeDTO->setNumIdTarefa(49);

      $objAtividadeRN = new AtividadeRN();
      $arrIdProcessos = InfraArray::converterArrInfraDTO($objAtividadeRN->listarRN0036($objAtividadeDTO),'IdProtocolo');

      $objRelProtocoloProtocoloRN = new RelProtocoloProtocoloRN();

      $numRegistros = count($arrIdProcessos);
      $n = 0;
      InfraDebug::getInstance()->setBolDebugInfra(false);

      InfraDebug::getInstance()->gravar('REMOVENDO DADOS DE CONTROLE INTERNO ANTIGOS DE PROCESSOS...');

      foreach($arrIdProcessos as $dblIdProcesso) {

        if ((++$n >=100 && $n%100==0) || $n==$numRegistros){
          InfraDebug::getInstance()->gravar($n.' DE '.$numRegistros);
        }

        //busca processos anexados
        $objRelProtocoloProtocoloDTO = new RelProtocoloProtocoloDTO();
        $objRelProtocoloProtocoloDTO->retDblIdProtocolo2();
        $objRelProtocoloProtocoloDTO->setStrStaAssociacao(RelProtocoloProtocoloRN::$TA_PROCEDIMENTO_ANEXADO);
        $objRelProtocoloProtocoloDTO->setDblIdProtocolo1($dblIdProcesso);
        $arrObjRelProtocoloProtocoloDTO = $objRelProtocoloProtocoloRN->listarRN0187($objRelProtocoloProtocoloDTO);

        $arrIdProtocolos = InfraArray::converterArrInfraDTO($arrObjRelProtocoloProtocoloDTO, 'IdProtocolo2');
        $arrIdProtocolos[] = $dblIdProcesso;

        //busca unidades de controle com acesso neste processo
        $objAtividadeDTO = new AtividadeDTO();
        $objAtividadeDTO->retNumIdUnidade();
        $objAtividadeDTO->setNumIdTarefa(49);
        $objAtividadeDTO->setDblIdProtocolo($dblIdProcesso);
        $arrIdUnidades = InfraArray::converterArrInfraDTO($objAtividadeRN->listarRN0036($objAtividadeDTO), 'IdUnidade');

        //remove andamentos de acesso automatico do processo
        BancoSEI::getInstance()->executarSql('delete from atividade where id_protocolo='.$dblIdProcesso.' and id_tarefa=49');

        //busca outros andamentos das unidades (se existirem)
        $objAtividadeDTO = new AtividadeDTO();
        $objAtividadeDTO->setDistinct(true);
        $objAtividadeDTO->retNumIdUnidade();
        $objAtividadeDTO->setDblIdProtocolo($dblIdProcesso);
        $objAtividadeDTO->setNumIdUnidade($arrIdUnidades,InfraDTO::$OPER_IN);
        $arrIdUnidadesOutroAndamento = InfraArray::converterArrInfraDTO($objAtividadeRN->listarRN0036($objAtividadeDTO), 'IdUnidade');

        //remove acessos das unidades que nao possuem outros andamentos
        foreach ($arrIdUnidades as $numIdUnidade) {
          if (!in_array($numIdUnidade,$arrIdUnidadesOutroAndamento)){
            BancoSEI::getInstance()->executarSql('delete from acesso where sta_tipo=\'R\' and id_unidade=' . $numIdUnidade . ' and id_protocolo='.$dblIdProcesso);
          }
        }
      }
      InfraDebug::getInstance()->setBolDebugInfra(true);

      //remove andamentos de acesso automatico de processos com nivel de acesso publico/sigiloso
      BancoSEI::getInstance()->executarSql('delete from atividade where id_tarefa=49');

      BancoSEI::getInstance()->executarSql('delete from tarefa where id_tarefa=49');

      $objControleInternoDTO = new ControleInternoDTO();
      $objControleInternoDTO->setDistinct(true);
      $objControleInternoDTO->retNumIdUnidadeControle();
      $objControleInternoDTO->retNumIdOrgaoControlado();
      $objControleInternoDTO->retNumIdTipoProcedimentoControlado();
      $objControleInternoDTO->setNumIdTipoProcedimentoControlado(null, InfraDTO::$OPER_DIFERENTE);

      $objControleInternoRN = new ControleInternoRN();
      $arrObjControleInternoDTO = InfraArray::indexarArrInfraDTO($objControleInternoRN->listar($objControleInternoDTO),'IdUnidadeControle',true);


      $objProcedimentoRN = new ProcedimentoRN();
      $objDocumentoRN = new DocumentoRN();
      $objAcessoRN = new AcessoRN();

      $arrProcessosControleInterno = array();

      InfraDebug::getInstance()->setBolDebugInfra(false);

      foreach($arrObjControleInternoDTO as $numIdUnidade => $arrObjControleInternoDTOUnidade) {

        foreach ($arrObjControleInternoDTOUnidade as $objControleInternoDTO) {

          InfraDebug::getInstance()->gravar('CRITERIO TIPO DE PROCESSO: '.$numIdUnidade.' / '.$objControleInternoDTO->getNumIdOrgaoControlado().' / '.$objControleInternoDTO->getNumIdTipoProcedimentoControlado());

          $objProcedimentoDTO = new ProcedimentoDTO();
          $objProcedimentoDTO->retDblIdProcedimento();
          $objProcedimentoDTO->setNumIdTipoProcedimento($objControleInternoDTO->getNumIdTipoProcedimentoControlado());
          $objProcedimentoDTO->setNumIdOrgaoUnidadeGeradoraProtocolo($objControleInternoDTO->getNumIdOrgaoControlado());
          $objProcedimentoDTO->setStrStaNivelAcessoGlobalProtocolo(ProtocoloRN::$NA_RESTRITO);
          $arrIdProcedimentoPartes = array_chunk(InfraArray::converterArrInfraDTO($objProcedimentoRN->listarRN0278($objProcedimentoDTO), 'IdProcedimento'), 100);

          if (count($arrIdProcedimentoPartes)) {

            $dto = new ControleInternoDTO();
            $dto->retNumIdControleInterno();
            $dto->setNumIdOrgaoControlado($objControleInternoDTO->getNumIdOrgaoControlado());
            $dto->setNumIdTipoProcedimentoControlado($objControleInternoDTO->getNumIdTipoProcedimentoControlado());
            $dto->setNumIdUnidadeControle($numIdUnidade);
            $arr = $objControleInternoRN->listar($dto);

            foreach($arr as $dto) {
              foreach ($arrIdProcedimentoPartes as $arrIdProcedimento) {
                $arrObjAcessoDTO = array();
                foreach ($arrIdProcedimento as $dblIdProcedimento) {
                  if (!isset($arrProcessosControleInterno[$dblIdProcedimento][$numIdUnidade][$dto->getNumIdControleInterno()])) {
                    $objAcessoDTO = new AcessoDTO();
                    $objAcessoDTO->setNumIdAcesso(null);
                    $objAcessoDTO->setNumIdUnidade($numIdUnidade);
                    $objAcessoDTO->setNumIdUsuario(null);
                    $objAcessoDTO->setDblIdProtocolo($dblIdProcedimento);
                    $objAcessoDTO->setNumIdControleInterno($dto->getNumIdControleInterno());
                    $objAcessoDTO->setStrStaTipo(AcessoRN::$TA_CONTROLE_INTERNO);
                    $arrObjAcessoDTO[] = $objAcessoDTO;

                    $arrProcessosControleInterno[$dblIdProcedimento][$numIdUnidade][$dto->getNumIdControleInterno()] = 0;
                  }
                }
                $objAcessoRN->cadastrarMultiplo($arrObjAcessoDTO);
              }
            }
          }
        }
      }

      $objControleInternoDTO = new ControleInternoDTO();
      $objControleInternoDTO->setDistinct(true);
      $objControleInternoDTO->retNumIdUnidadeControle();
      $objControleInternoDTO->retNumIdOrgaoControlado();
      $objControleInternoDTO->retNumIdSerieControlada();
      $objControleInternoDTO->setNumIdSerieControlada(null,InfraDTO::$OPER_DIFERENTE);

      $objControleInternoRN = new ControleInternoRN();
      $arrObjControleInternoDTO = InfraArray::indexarArrInfraDTO($objControleInternoRN->listar($objControleInternoDTO),'IdUnidadeControle',true);

      foreach($arrObjControleInternoDTO as $numIdUnidade => $arrObjControleInternoDTOUnidade) {

        foreach ($arrObjControleInternoDTOUnidade as $objControleInternoDTO) {

          InfraDebug::getInstance()->gravar('CRITERIO TIPO DE DOCUMENTO: '.$numIdUnidade.' / '.$objControleInternoDTO->getNumIdOrgaoControlado().' / '.$objControleInternoDTO->getNumIdSerieControlada());

          $objDocumentoDTO = new DocumentoDTO();
          $objDocumentoDTO->setDistinct(true);
          $objDocumentoDTO->retDblIdProcedimento();
          $objDocumentoDTO->setNumIdSerie($objControleInternoDTO->getNumIdSerieControlada());
          $objDocumentoDTO->setNumIdOrgaoUnidadeGeradoraProtocolo($objControleInternoDTO->getNumIdOrgaoControlado());
          $objDocumentoDTO->setStrStaNivelAcessoGlobalProtocolo(ProtocoloRN::$NA_RESTRITO);
          $arrIdProcedimentoPartes = array_chunk(InfraArray::converterArrInfraDTO($objDocumentoRN->listarRN0008($objDocumentoDTO),'IdProcedimento'),100);

          if (count($arrIdProcedimentoPartes)) {

            $dto = new ControleInternoDTO();
            $dto->retNumIdControleInterno();
            $dto->setNumIdOrgaoControlado($objControleInternoDTO->getNumIdOrgaoControlado());
            $dto->setNumIdSerieControlada($objControleInternoDTO->getNumIdSerieControlada());
            $dto->setNumIdUnidadeControle($numIdUnidade);
            $arr = $objControleInternoRN->listar($dto);

            foreach($arr as $dto) {

              foreach ($arrIdProcedimentoPartes as $arrIdProcedimento) {
                $arrObjAcessoDTO = array();
                foreach ($arrIdProcedimento as $dblIdProcedimento) {
                  if (!isset($arrProcessosControleInterno[$dblIdProcedimento][$numIdUnidade][$dto->getNumIdControleInterno()])) {
                    $objAcessoDTO = new AcessoDTO();
                    $objAcessoDTO->setNumIdAcesso(null);
                    $objAcessoDTO->setNumIdUnidade($numIdUnidade);
                    $objAcessoDTO->setNumIdUsuario(null);
                    $objAcessoDTO->setDblIdProtocolo($dblIdProcedimento);
                    $objAcessoDTO->setNumIdControleInterno($dto->getNumIdControleInterno());
                    $objAcessoDTO->setStrStaTipo(AcessoRN::$TA_CONTROLE_INTERNO);
                    $arrObjAcessoDTO[] = $objAcessoDTO;

                    $arrProcessosControleInterno[$dblIdProcedimento][$numIdUnidade][$dto->getNumIdControleInterno()] = 0;
                  }
                }
                $objAcessoRN->cadastrarMultiplo($arrObjAcessoDTO);
              }
            }
          }
        }
      }

      $numRegistros = count($arrProcessosControleInterno);

      $n = 0;
      foreach($arrProcessosControleInterno as $dblIdProcedimento => $arrIdUnidades) {

        if ((++$n >=500 && $n%500==0) || $n==$numRegistros){
          InfraDebug::getInstance()->gravar('VERIFICANDO PROCESSOS ANEXADOS: '.$n.' DE '.$numRegistros);
        }

        $objRelProtocoloProtocoloDTO = new RelProtocoloProtocoloDTO();
        $objRelProtocoloProtocoloDTO->retDblIdProtocolo1();
        $objRelProtocoloProtocoloDTO->setStrStaAssociacao(RelProtocoloProtocoloRN::$TA_PROCEDIMENTO_ANEXADO);
        $objRelProtocoloProtocoloDTO->setDblIdProtocolo2($dblIdProcedimento);
        $objRelProtocoloProtocoloDTO = $objRelProtocoloProtocoloRN->consultarRN0841($objRelProtocoloProtocoloDTO);

        if ($objRelProtocoloProtocoloDTO!=null){
          $dblIdProcessoPai = $objRelProtocoloProtocoloDTO->getDblIdProtocolo1();
        }else{
          $dblIdProcessoPai = $dblIdProcedimento;
        }

        $objRelProtocoloProtocoloDTO = new RelProtocoloProtocoloDTO();
        $objRelProtocoloProtocoloDTO->retDblIdProtocolo2();
        $objRelProtocoloProtocoloDTO->setStrStaAssociacao(RelProtocoloProtocoloRN::$TA_PROCEDIMENTO_ANEXADO);
        $objRelProtocoloProtocoloDTO->setDblIdProtocolo1($dblIdProcessoPai);

        $objRelProtocoloProtocoloRN = new RelProtocoloProtocoloRN();
        $arrIdProcessos = InfraArray::converterArrInfraDTO($objRelProtocoloProtocoloRN->listarRN0187($objRelProtocoloProtocoloDTO), 'IdProtocolo2');

        $arrIdProcessos[] = $dblIdProcessoPai;

        foreach($arrIdProcessos as $dblIdProcessosAnexosOuAnexados) {
          foreach (array_keys($arrIdUnidades) as $numIdUnidade) {
            foreach(array_keys($arrIdUnidades[$numIdUnidade]) as $numIdControleInterno) {
              if (!isset($arrProcessosControleInterno[$dblIdProcessosAnexosOuAnexados][$numIdUnidade][$numIdControleInterno])) {

                $objAcessoDTO = new AcessoDTO();
                $objAcessoDTO->setNumIdAcesso(null);
                $objAcessoDTO->setNumIdUnidade($numIdUnidade);
                $objAcessoDTO->setNumIdUsuario(null);
                $objAcessoDTO->setDblIdProtocolo($dblIdProcessosAnexosOuAnexados);
                $objAcessoDTO->setNumIdControleInterno($numIdControleInterno);
                $objAcessoDTO->setStrStaTipo(AcessoRN::$TA_CONTROLE_INTERNO);
                $objAcessoRN->cadastrar($objAcessoDTO);
                $arrProcessosControleInterno[$dblIdProcessosAnexosOuAnexados][$numIdUnidade][$numIdControleInterno] = 0;
              }
            }
          }
        }
      }

      InfraDebug::getInstance()->setBolDebugInfra(true);

    }catch(Exception $e){
      throw new InfraException('Erro processando dados de Controle Interno.', $e);
    }
  }

  protected function fixPopularCargoTratamentoVocativoControlado(){

    try{

      InfraDebug::getInstance()->gravar('POPULANDO CARGOS, TRATAMENTOS E VOCATIVOS...');

      InfraDebug::getInstance()->setBolDebugInfra(false);

      $strConteudo = 'A Sua Excelência o Senhor;Almirante da Marinha do Brasil;Senhor Almirante;M
        A Sua Excelência o Senhor;Brigadeiro da Força Aérea Brasileira;Senhor Brigadeiro;M
        Ao Senhor;Chefe de Gabinete;Senhor Chefe de Gabinete;M
        Ao Senhor;Cidadão;Senhor;M
        A Senhora;Cidadão;Senhora;F
        A Sua Excelência o Senhor;Cônsul;Senhor Cônsul;M
        A Sua Excelência a Senhora;Consulesa;Senhora Consulesa;F
        Ao Senhor;Coordenador;Senhor Coordenador;M
        A Senhora;Coordenadora;Senhora Coordenadora;F
        A Senhora;Coordenadora-Geral;Senhora Coordenadora-Geral;F
        Ao Senhor;Coordenador-Geral;Senhor Coordenador-Geral;M
        A Sua Excelência a Senhora;Delegada de Polícia;Senhora Delegada;F
        A Sua Excelência a Senhora;Delegada de Polícia Federal;Senhora Delegada;F
        A Sua Excelência o Senhor;Delegado de Polícia;Senhor Delegado;M
        A Sua Excelência o Senhor;Delegado de Polícia Federal;Senhor Delegado;M
        A Sua Excelência a Senhora;Deputada Estadual;Senhora Deputada;F
        A Sua Excelência a Senhora;Deputada Federal;Senhora Deputada;F
        A Sua Excelência o Senhor;Deputado Estadual;Senhor Deputado;M
        A Sua Excelência o Senhor;Deputado Federal;Senhor Deputado;M
        A Sua Excelência o Senhor;Desembargador de Justiça;Senhor Desembargador;M
        A Sua Excelência o Senhor;Desembargador Federal;Senhor Desembargador;M
        A Sua Excelência a Senhora;Desembargadora de Justiça;Senhora Desembargadora;F
        A Sua Excelência a Senhora;Desembargadora Federal;Senhora Desembargadora;F
        Ao Senhor;Diretor;Senhor Diretor;M
        A Senhora;Diretora;Senhora Diretora;F
        A Sua Excelência o Senhor;Embaixador;Senhor Embaixador;M
        A Sua Excelência a Senhora;Embaixadora;Senhora Embaixadora;F
        A Sua Excelência o Senhor;General do Exército Brasileiro;Senhor General;M
        A Sua Excelência o Senhor;Governador;Senhor Governador;M
        A Sua Excelência a Senhora;Governadora;Senhora Governadora;F
        A Sua Excelência o Senhor;Juiz de Direito;Senhor Juiz;M
        A Sua Excelência o Senhor;Juiz Federal;Senhor Juiz;M
        A Sua Excelência a Senhora;Juíza de Direito;Senhora Juíza;F
        A Sua Excelência a Senhora;Juíza Federal;Senhora Juíza;F
        A Sua Excelência o Senhor;Marechal do Exército Brasileiro;Senhor Marechal;M
        A Sua Excelência a Senhora;Ministra de Estado;Senhora Ministra;F
        A Sua Excelência o Senhor;Ministro de Estado;Senhor Chefe da Casa Militar;M
        A Sua Excelência o Senhor;Ministro de Estado;Senhor Ministro;M
        A Sua Excelência a Senhora;Prefeita Municipal;Senhora Prefeita;F
        A Sua Excelência o Senhor;Prefeito Municipal;Senhor Prefeito;M
        A Senhora;Presidenta;Senhora Presidenta;F
        A Sua Excelência a Senhora;Presidenta da Assembleia Legislativa;Senhora Presidenta da Assembleia Legislativa;F
        A Sua Excelência a Senhora;Presidenta da Câmara Legislativa;Senhora Presidenta da Câmara Legislativa;F
        A Sua Excelência a Senhora;Presidenta da Câmara Municipal;Senhora Presidenta da Câmara Municipal;F
        A Sua Excelência a Senhora;Presidenta da República;Excelentíssima Senhora Presidenta da República;F
        A Sua Excelência a Senhora;Presidenta do Congresso Nacional;Excelentíssima Senhora Presidenta;F
        A Sua Excelência a Senhora;Presidenta do Supremo Tribunal Federal;Excelentíssima Senhora Presidenta;F
        Ao Senhor;Presidente;Senhor Presidente;M
        A Sua Excelência o Senhor;Presidente da Assembleia Legislativa;Senhor Presidente da Assembleia Legislativa;M
        A Sua Excelência o Senhor;Presidente da Câmara Legislativa;Senhor Presidente da Câmara Legislativa;M
        A Sua Excelência o Senhor;Presidente da Câmara Municipal;Senhor Presidente da Câmara Municipal;M
        A Sua Excelência o Senhor;Presidente da República;Excelentíssimo Senhor Presidente da República;M
        A Sua Excelência o Senhor;Presidente do Congresso Nacional;Excelentíssimo Senhor Presidente;M
        A Sua Excelência o Senhor;Presidente do Supremo Tribunal Federal;Excelentíssimo Senhor Presidente;M
        A Sua Excelência o Senhor;Procurador da República;Senhor Procurador;M
        A Sua Excelência a Senhora;Procuradora da República;Senhora Procuradora;F
        A Sua Excelência o Senhor;Procurador do Estado;Senhor Procurador;M
        A Sua Excelência a Senhora;Procuradora do Estado;Senhora Procuradora;F
        A Sua Excelência o Senhor;Promotor de Justiça;Senhor Promotor;M
        A Sua Excelência a Senhora;Promotora de Justiça;Senhora Promotora;F
        Ao Senhor;Reitor;Magnífico Reitor;M
        A Senhora;Reitora;Magnífica Reitora;F
        A Senhora;Secretária;Senhora Secretária;F
        A Sua Excelência a Senhora;Secretária de Estado;Senhora Secretária;F
        Ao Senhor;Secretário;Senhor Secretário;M
        A Sua Excelência o Senhor;Secretário de Estado;Senhor Secretário;M
        A Sua Excelência o Senhor;Secretário-Adjunto;Senhor Secretário;M
        A Sua Excelência o Senhor;Secretário-Executivo;Senhor Secretário;M
        A Sua Excelência o Senhor;Secretário-Executivo Adjunto;Senhor Secretário;M
        A Sua Excelência o Senhor;Secretário-Executivo Substituto;Senhor Secretário;M
        A Sua Excelência o Senhor;Senador da República;Senhor Senador;M
        A Sua Excelência a Senhora;Senadora da República;Senhora Senadora;F
        Ao Senhor;Superintendente;Senhor Superintendente;M
        A Senhora;Superintendente;Senhora Superintendente;F
        Ao Senhor;Vereador;Senhor Vereador;M
        A Senhora;Vereadora;Senhora Vereadora;F
        Ao Senhor;Vice-Presidente;Senhor Vice-Presidente;M
        A Sua Excelência o Senhor;Vice-Presidente da República;Senhor Vice-Presidente da República;M
        Ao Senhor;Vice-Reitor;Senhor Vice-Reitor;M
        A Senhora;Vice-Reitora;Senhora Vice-Reitora;F
        Ao Senhor;Gerente;Senhor Gerente;M
        A Senhora;Gerente;Senhora Gerente;F';

$arrLinhas = explode("\n",$strConteudo);

$objTratamentoRN = new TratamentoRN();
$objCargoRN = new CargoRN();
$objVocativoRN = new VocativoRN();

$arrIdTratamento = array();
$arrIdVocativo = array();

foreach($arrLinhas as $strLinha){
  $arrColunas = explode(';', $strLinha);

  $arrColunas[0] = trim($arrColunas[0]);
  $arrColunas[1] = trim($arrColunas[1]);
  $arrColunas[2] = trim($arrColunas[2]);
  $arrColunas[3] = trim($arrColunas[3]);

  $objCargoDTO = new CargoDTO();
  $objCargoDTO->setBolExclusaoLogica(false);
  $objCargoDTO->retNumIdCargo();
  $objCargoDTO->setStrExpressao($arrColunas[1]);

  $objCargoDTO->adicionarCriterio(array('StaGenero','StaGenero'),
    array(InfraDTO::$OPER_IGUAL, InfraDTO::$OPER_IGUAL),
    array(null, $arrColunas[3]),
    InfraDTO::$OPER_LOGICO_OR);

  $objCargoDTO->setNumMaxRegistrosRetorno(1);

  if ($objCargoRN->consultarRN0301($objCargoDTO) == null) {

    if (!isset($arrIdTratamento[$arrColunas[0]])) {

      $objTratamentoDTO = new TratamentoDTO();
      $objTratamentoDTO->setBolExclusaoLogica(false);
      $objTratamentoDTO->retNumIdTratamento();
      $objTratamentoDTO->setStrExpressao($arrColunas[0]);
      $objTratamentoDTO->setNumMaxRegistrosRetorno(1);
      $objTratamentoDTO = $objTratamentoRN->consultarRN0317($objTratamentoDTO);

      if ($objTratamentoDTO == null) {
        $objTratamentoDTO = new TratamentoDTO();
        $objTratamentoDTO->setNumIdTratamento(null);
        $objTratamentoDTO->setStrExpressao($arrColunas[0]);
        $objTratamentoDTO->setStrSinAtivo('S');
        $objTratamentoDTO = $objTratamentoRN->cadastrarRN0315($objTratamentoDTO);
      }

      $arrIdTratamento[$arrColunas[0]] = $objTratamentoDTO->getNumIdTratamento();
    }

    if (!isset($arrIdVocativo[$arrColunas[2]])) {

      $objVocativoDTO = new VocativoDTO();
      $objVocativoDTO->setBolExclusaoLogica(false);
      $objVocativoDTO->retNumIdVocativo();
      $objVocativoDTO->setStrExpressao($arrColunas[2]);
      $objVocativoDTO->setNumMaxRegistrosRetorno(1);
      $objVocativoDTO = $objVocativoRN->consultarRN0309($objVocativoDTO);

      if ($objVocativoDTO == null) {
        $objVocativoDTO = new VocativoDTO();
        $objVocativoDTO->setNumIdVocativo(null);
        $objVocativoDTO->setStrExpressao($arrColunas[2]);
        $objVocativoDTO->setStrSinAtivo('S');
        $objVocativoDTO = $objVocativoRN->cadastrarRN0307($objVocativoDTO);
      }

      $arrIdVocativo[$arrColunas[2]] = $objVocativoDTO->getNumIdVocativo();
    }

    $objCargoDTO = new CargoDTO();
    $objCargoDTO->setNumIdCargo(null);
    $objCargoDTO->setStrExpressao($arrColunas[1]);
    $objCargoDTO->setNumIdTratamento($arrIdTratamento[$arrColunas[0]]);
    $objCargoDTO->setNumIdVocativo($arrIdVocativo[$arrColunas[2]]);
    $objCargoDTO->setStrStaGenero($arrColunas[3]);
    $objCargoDTO->setStrSinAtivo('S');
    $objCargoRN->cadastrarRN0299($objCargoDTO);
  }
}

InfraDebug::getInstance()->setBolDebugInfra(true);

    }catch(Exception $e){
      throw new InfraException('Erro populando dados de Cargo, Tratamento e Vocativo.', $e);
    }
  }

  public function migrarDadosDocumentos(){

    try{

      $rsProtocolos = BancoSEI::getInstance()->consultarSql('select '.BancoSEI::getInstance()->formatarSelecaoDbl('protocolo', 'id_protocolo', 'idprotocolo').' from protocolo where sta_protocolo='.BancoSEI::getInstance()->formatarGravacaoStr(ProtocoloRN::$TP_DOCUMENTO_GERADO));

      $rsAssinaturas = BancoSEI::getInstance()->consultarSql('select distinct '.BancoSEI::getInstance()->formatarSelecaoDbl('assinatura', 'id_documento', 'idprotocolo').' from assinatura inner join protocolo on assinatura.id_documento=protocolo.id_protocolo and protocolo.sta_protocolo='.BancoSEI::getInstance()->formatarGravacaoStr(ProtocoloRN::$TP_DOCUMENTO_RECEBIDO));

      $rsProtocolos = array_merge($rsProtocolos, $rsAssinaturas);

      $numRegistros = count($rsProtocolos);
      $n = 0;

      $objDocumentoConteudoBD = new DocumentoConteudoBD(BancoSEI::getInstance());


      InfraDebug::getInstance()->setBolDebugInfra(false);
      foreach($rsProtocolos as $item){

        $dblIdProtocolo = BancoSEI::getInstance()->formatarLeituraDbl($item['idprotocolo']);

        if ((++$n >=1000 && $n%1000==0) || $n==$numRegistros){
          InfraDebug::getInstance()->gravar('MIGRANDO DADOS DE DOCUMENTOS: '.$n.' DE '.$numRegistros);
        }

        $rs = BancoSEI::getInstance()->consultarSql('select '.
          BancoSEI::getInstance()->formatarSelecaoNum('documento', 'conteudo', 'documentoconteudo').','.
          BancoSEI::getInstance()->formatarSelecaoStr('documento', 'conteudo_assinatura', 'conteudoassinatura').','.
          BancoSEI::getInstance()->formatarSelecaoStr('documento', 'crc_assinatura', 'crcassinatura').','.
          BancoSEI::getInstance()->formatarSelecaoStr('documento', 'qr_code_assinatura', 'qrcodeassinatura').
          ' from documento where id_documento='.BancoSEI::getInstance()->formatarGravacaoDbl($dblIdProtocolo));


        if (count($rs)==1) {

          $objDocumentoConteudoDTO = new DocumentoConteudoDTO();
          $objDocumentoConteudoDTO->setStrConteudo(BancoSEI::getInstance()->formatarLeituraStr($rs[0]['documentoconteudo']));
          $objDocumentoConteudoDTO->setStrConteudoAssinatura(BancoSEI::getInstance()->formatarLeituraStr($rs[0]['conteudoassinatura']));
          $objDocumentoConteudoDTO->setStrCrcAssinatura(BancoSEI::getInstance()->formatarLeituraStr($rs[0]['crcassinatura']));
          $objDocumentoConteudoDTO->setStrQrCodeAssinatura(BancoSEI::getInstance()->formatarLeituraStr($rs[0]['qrcodeassinatura']));
          $objDocumentoConteudoDTO->setDblIdDocumento($dblIdProtocolo);
          $objDocumentoConteudoBD->cadastrar($objDocumentoConteudoDTO);

          BancoSEI::getInstance()->executarSql('update documento set conteudo=null,conteudo_assinatura=null,crc_assinatura=null,qr_code_assinatura=null where id_documento=' . BancoSEI::getInstance()->formatarGravacaoDbl($dblIdProtocolo));
        }
      }
      InfraDebug::getInstance()->setBolDebugInfra(true);
    }catch(Exception $e){
      throw new InfraException('Erro migrando conteúdo de documentos internos.', $e);
    }
  }

  public function fixSinAtivoContatos(){

    try{

      InfraDebug::getInstance()->setBolDebugInfra(false);

      $objContatoDTO = new ContatoDTO();
      $objContatoDTO->setBolExclusaoLogica(false);
      $objContatoDTO->retNumIdContato();
      $objContatoDTO->retStrSinAtivo();

      $objContatoRN = new ContatoRN();
      $arrObjContatoDTO = InfraArray::indexarArrInfraDTO($objContatoRN->listarRN0325($objContatoDTO),'IdContato');

      $objContatoBD = new ContatoBD(BancoSEI::getInstance());

      InfraDebug::getInstance()->gravar('ORGAOS...');

      $objOrgaoDTO = new OrgaoDTO();
      $objOrgaoDTO->setBolExclusaoLogica(false);
      $objOrgaoDTO->retNumIdContato();
      $objOrgaoDTO->retStrSinAtivo();

      $objOrgaoRN = new OrgaoRN();
      $arrObjOrgaoDTO = InfraArray::indexarArrInfraDTO($objOrgaoRN->listarRN1353($objOrgaoDTO),'IdContato');

      $n = 0;
      foreach($arrObjOrgaoDTO as $numIdContato => $objOrgaoDTO){
        if ($arrObjContatoDTO[$numIdContato]->getStrSinAtivo()!=$objOrgaoDTO->getStrSinAtivo()){
          $objContatoDTO = new ContatoDTO();
          $objContatoDTO->setStrSinAtivo($objOrgaoDTO->getStrSinAtivo());
          $objContatoDTO->setNumIdContato($numIdContato);
          $objContatoBD->alterar($objContatoDTO);
          $n++;
        }
      }
      InfraDebug::getInstance()->gravar($n.' REGISTROS ATUALIZADOS');


      InfraDebug::getInstance()->gravar('UNIDADES...');

      $objUnidadeDTO = new UnidadeDTO();
      $objUnidadeDTO->setBolExclusaoLogica(false);
      $objUnidadeDTO->retNumIdContato();
      $objUnidadeDTO->retStrSinAtivo();

      $objUnidadeRN = new UnidadeRN();
      $arrObjUnidadeDTO = InfraArray::indexarArrInfraDTO($objUnidadeRN->listarRN0127($objUnidadeDTO),'IdContato');

      $n = 0;
      foreach($arrObjUnidadeDTO as $numIdContato => $objUnidadeDTO){
        if ($arrObjContatoDTO[$numIdContato]->getStrSinAtivo()!=$objUnidadeDTO->getStrSinAtivo()){
          $objContatoDTO = new ContatoDTO();
          $objContatoDTO->setStrSinAtivo($objUnidadeDTO->getStrSinAtivo());
          $objContatoDTO->setNumIdContato($numIdContato);
          $objContatoBD->alterar($objContatoDTO);
          $n++;
        }
      }
      InfraDebug::getInstance()->gravar($n.' REGISTROS ATUALIZADOS');

      InfraDebug::getInstance()->gravar('USUARIOS...');

      $objUsuarioDTO = new UsuarioDTO();
      $objUsuarioDTO->setBolExclusaoLogica(false);
      $objUsuarioDTO->retNumIdContato();

      $objUsuarioRN = new UsuarioRN();
      $arrIdContatoUsuarios = InfraArray::converterArrInfraDTO($objUsuarioRN->listarRN0490($objUsuarioDTO),'IdContato');

      $n = 0;
      foreach($arrIdContatoUsuarios as $numIdContato){
        if ($arrObjContatoDTO[$numIdContato]->getStrSinAtivo()=='N') {
          $objContatoDTO = new ContatoDTO();
          $objContatoDTO->setStrSinAtivo('S');
          $objContatoDTO->setNumIdContato($numIdContato);
          $objContatoBD->alterar($objContatoDTO);
          $n++;
        }
      }
      InfraDebug::getInstance()->gravar($n.' REGISTROS ATUALIZADOS');

      InfraDebug::getInstance()->setBolDebugInfra(true);

    }catch(Exception $e){
      throw new InfraException('Erro sincronizando sinalizadores de exclusão lógica.', $e);
    }
  }

  public function fixPontoControle(){

    try{

      InfraDebug::getInstance()->setBolDebugInfra(false);

      $rs = BancoSEI::getInstance()->consultarSql('select '.BancoSEI::getInstance()->formatarSelecaoNum('atributo_andamento_situacao', 'id_andamento_situacao', null).','.BancoSEI::getInstance()->formatarSelecaoStr('atributo_andamento_situacao', 'id_origem', null).' from atributo_andamento_situacao where id_origem is not null');


      $objSituacaoDTO = new SituacaoDTO();
      $objSituacaoDTO->setBolExclusaoLogica(false);
      $objSituacaoDTO->retNumIdSituacao();

      $objSituacaoRN = new SituacaoRN();
      $arrIdSituacoes = InfraArray::converterArrInfraDTO($objSituacaoRN->listar($objSituacaoDTO),'IdSituacao');

      $numRegistros = count($rs);
      $n = 0;
      $objAndamentoSituacaoBD = new AndamentoSituacaoBD(BancoSEI::getInstance());
      foreach($rs as $item){

        if ((++$n >=100 && $n%100==0) || $n==$numRegistros){
          InfraDebug::getInstance()->gravar('PONTOS DE CONTROLE [ATRIBUTOS]: '.$n.' DE '.$numRegistros);
        }

        if (in_array(BancoSEI::getInstance()->formatarLeituraStr($item['id_origem']),$arrIdSituacoes)) {
          $objAndamentoSituacaoDTO = new AndamentoSituacaoDTO();
          $objAndamentoSituacaoDTO->setNumIdAndamentoSituacao(BancoSEI::getInstance()->formatarLeituraNum($item['id_andamento_situacao']));
          $objAndamentoSituacaoDTO->setNumIdSituacao(BancoSEI::getInstance()->formatarLeituraStr($item['id_origem']));
          $objAndamentoSituacaoBD->alterar($objAndamentoSituacaoDTO);
        }
      }

      $rs = BancoSEI::getInstance()->consultarSql('select '.BancoSEI::getInstance()->formatarSelecaoDbl('rel_proced_situacao_unidade', 'id_procedimento', null).','.BancoSEI::getInstance()->formatarSelecaoDbl('rel_proced_situacao_unidade', 'id_unidade', null).' from rel_proced_situacao_unidade');

      $numRegistros = count($rs);
      $n = 0;

      $objAndamentoSituacaoRN = new AndamentoSituacaoRN();
      foreach($rs as $item){

        if ((++$n >=100 && $n%100==0) || $n==$numRegistros){
          InfraDebug::getInstance()->gravar('PONTOS DE CONTROLE [SITUACAO]: '.$n.' DE '.$numRegistros);
        }

        $objAndamentoSituacaoDTO = new AndamentoSituacaoDTO();
        $objAndamentoSituacaoDTO->retNumIdAndamentoSituacao();
        $objAndamentoSituacaoDTO->setDblIdProcedimento(BancoSEI::getInstance()->formatarLeituraDbl($item['id_procedimento']));
        $objAndamentoSituacaoDTO->setNumIdUnidade(BancoSEI::getInstance()->formatarLeituraNum($item['id_unidade']));
        $objAndamentoSituacaoDTO->setOrdNumIdAndamentoSituacao(InfraDTO::$TIPO_ORDENACAO_DESC);
        $objAndamentoSituacaoDTO->setNumMaxRegistrosRetorno(1);

        $objAndamentoSituacaoDTO = $objAndamentoSituacaoRN->consultar($objAndamentoSituacaoDTO);
        if ($objAndamentoSituacaoDTO!=null) {
          $objAndamentoSituacaoDTO->setStrSinUltimo('S');
          $objAndamentoSituacaoBD->alterar($objAndamentoSituacaoDTO);
        }
      }

      InfraDebug::getInstance()->setBolDebugInfra(true);

    }catch(Exception $e){
      throw new InfraException('Erro atualizando Pontos de Controle.', $e);
    }
  }

  public function fixAssuntos(){

    try{

      InfraDebug::getInstance()->setBolDebugInfra(false);

      InfraDebug::getInstance()->gravar('ATUALIZANDO ASSUNTOS...');

      $rs = BancoSEI::getInstance()->consultarSql('select '.
        BancoSEI::getInstance()->formatarSelecaoNum('assunto', 'id_assunto', null).','.
        BancoSEI::getInstance()->formatarSelecaoNum('assunto', 'maior_tempo_corrente', null).','.
        BancoSEI::getInstance()->formatarSelecaoNum('assunto', 'menor_tempo_corrente', null).','.
        BancoSEI::getInstance()->formatarSelecaoStr('assunto', 'sin_elimina_maior_corrente', null).','.
        BancoSEI::getInstance()->formatarSelecaoStr('assunto', 'sin_elimina_menor_corrente', null).','.
        BancoSEI::getInstance()->formatarSelecaoNum('assunto', 'maior_tempo_intermediario', null).','.
        BancoSEI::getInstance()->formatarSelecaoNum('assunto', 'menor_tempo_intermediario', null).','.
        BancoSEI::getInstance()->formatarSelecaoStr('assunto', 'sin_elimina_menor_intermed', null).','.
        BancoSEI::getInstance()->formatarSelecaoStr('assunto', 'sin_elimina_maior_intermed', null).','.
        BancoSEI::getInstance()->formatarSelecaoStr('assunto', 'sin_suficiente', null).
        ' from assunto');

      $numRegistros = count($rs);
      $n = 0;

      $objAssuntoBD = new AssuntoBD(BancoSEI::getInstance());
      foreach($rs as $item){

        if ((++$n >=100 && $n%100==0) || $n==$numRegistros){
          InfraDebug::getInstance()->gravar($n.' DE '.$numRegistros);
        }

        $numIdAssunto = BancoSEI::getInstance()->formatarLeituraNum($item['id_assunto']);
        $numMaiorTempoCorrente = BancoSEI::getInstance()->formatarLeituraNum($item['maior_tempo_corrente']);
        $numMenorTempoCorrente = BancoSEI::getInstance()->formatarLeituraNum($item['menor_tempo_corrente']);
        $strSinEliminaMaiorCorrente = BancoSEI::getInstance()->formatarLeituraStr($item['sin_elimina_maior_corrente']);
        $strSinEliminaMenorCorrente = BancoSEI::getInstance()->formatarLeituraStr($item['sin_elimina_menor_corrente']);
        $numMaiorTempoIntermediario = BancoSEI::getInstance()->formatarLeituraNum($item['maior_tempo_intermediario']);
        $numMenorTempoIntermediario = BancoSEI::getInstance()->formatarLeituraNum($item['menor_tempo_intermediario']);
        $strSinEliminaMaiorIntermediario = BancoSEI::getInstance()->formatarLeituraStr($item['sin_elimina_maior_intermed']);
        $strSinEliminaMenorIntermediario = BancoSEI::getInstance()->formatarLeituraStr($item['sin_elimina_menor_intermed']);
        $strSinSuficiente = BancoSEI::getInstance()->formatarLeituraStr($item['sin_suficiente']);


        $objAssuntoDTO = new AssuntoDTO();

        if ($numMaiorTempoCorrente > $numMenorTempoCorrente){
          $objAssuntoDTO->setNumPrazoCorrente($numMaiorTempoCorrente);
        }else{
          $objAssuntoDTO->setNumPrazoCorrente($numMenorTempoCorrente);
        }


        if ($numMaiorTempoIntermediario > $numMenorTempoIntermediario) {
          $objAssuntoDTO->setNumPrazoIntermediario($numMaiorTempoIntermediario);
        }else{
          $objAssuntoDTO->setNumPrazoIntermediario($numMenorTempoIntermediario);
        }

        $strStaDestinacao = 'G';
        if ($strSinEliminaMaiorIntermediario=='S' && $strSinEliminaMenorIntermediario=='S'){
          $strStaDestinacao = 'E';
        }else if ($strSinEliminaMaiorCorrente=='S' && $strSinEliminaMenorCorrente=='S'){
          $strStaDestinacao = 'E';
        }

        $objAssuntoDTO->setStrStaDestinacao($strStaDestinacao);

        if ($strSinSuficiente=='S'){
          $objAssuntoDTO->setStrSinEstrutural('N');
        }else{

          $rsTiposProcesso = BancoSEI::getInstance()->consultarSql('select count(*) as total from rel_tipo_procedimento_assunto where id_assunto='.BancoSEI::getInstance()->formatarGravacaoNum($numIdAssunto));

          if ($rsTiposProcesso[0]['total']==0) {

            $rsTiposDocumento = BancoSEI::getInstance()->consultarSql('select count(*) as total from rel_serie_assunto where id_assunto=' . BancoSEI::getInstance()->formatarGravacaoNum($numIdAssunto));

            if ($rsTiposDocumento[0]['total']==0) {
              $rsProtocolos = BancoSEI::getInstance()->consultarSql('select count(*) as total from rel_protocolo_assunto where id_assunto=' . BancoSEI::getInstance()->formatarGravacaoNum($numIdAssunto));

              if ($rsProtocolos[0]['total']==0) {

                $objAssuntoDTO->setStrSinEstrutural('S');

              }else{
                $objAssuntoDTO->setStrSinEstrutural('N');
              }

            }else{
              $objAssuntoDTO->setStrSinEstrutural('N');
            }
          }else{
            $objAssuntoDTO->setStrSinEstrutural('N');
          }
        }

        $objAssuntoDTO->setNumIdAssunto($numIdAssunto);

        $objAssuntoBD->alterar($objAssuntoDTO);

      }

      InfraDebug::getInstance()->setBolDebugInfra(true);

    }catch(Exception $e){
      throw new InfraException('Erro atualizando Assuntos.', $e);
    }
  }

  public function fixArquivamento() {
    try{

      InfraDebug::getInstance()->setBolDebugInfra(false);

      InfraDebug::getInstance()->gravar('ATUALIZANDO ANDAMENTOS DE LOCALIZADORES...');

      $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
      $objAtributoAndamentoDTO->retNumIdAtributoAndamento();
      $objAtributoAndamentoDTO->retStrIdOrigem();
      $objAtributoAndamentoDTO->setStrNome('LOCALIZADOR');

      $objAtributoAndamentoRN = new AtributoAndamentoRN();
      $arrObjAtributoAndamentoDTO = $objAtributoAndamentoRN->listarRN1367($objAtributoAndamentoDTO);

      $numRegistros = count($arrObjAtributoAndamentoDTO);
      $n = 0;

      $objAtributoAndamentoBD = new AtributoAndamentoBD(BancoSEI::getInstance());
      foreach($arrObjAtributoAndamentoDTO as $dto){

        if ((++$n >=1000 && $n%1000==0) || $n==$numRegistros){
          InfraDebug::getInstance()->gravar('ANDAMENTO: '.$n.' DE '.$numRegistros);
        }

        $arr = explode('¥',$dto->getStrIdOrigem());
        $dto->setStrIdOrigem($arr[0]);

        $objAtributoAndamentoBD->alterar($dto);
      }


      $objAtributoAndamentoRN = new AtributoAndamentoRN();
      $objArquivamentoRN = new ArquivamentoRN();
      $objArquivamentoBD = new ArquivamentoBD(BancoSEI::getInstance());

      InfraDebug::getInstance()->gravar('ATUALIZANDO DOCUMENTOS COM ARQUIVAMENTO...');

      $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
      $objAtributoAndamentoDTO->retNumIdAtividade();
      $objAtributoAndamentoDTO->retStrIdOrigem();
      $objAtributoAndamentoDTO->setStrNome('DOCUMENTO');
      $objAtributoAndamentoDTO->setNumIdTarefaAtividade(TarefaRN::$TI_ARQUIVAMENTO);
      $objAtributoAndamentoDTO->setOrdNumIdAtividade(InfraDTO::$TIPO_ORDENACAO_ASC);

      $arrObjAtributoAndamentoDTO = InfraArray::indexarArrInfraDTO($objAtributoAndamentoRN->listarRN1367($objAtributoAndamentoDTO),'IdOrigem');

      $n = 0;
      $numRegistros = count($arrObjAtributoAndamentoDTO);

      foreach($arrObjAtributoAndamentoDTO as $objAtributoAndamentoDTO){

        if ((++$n >=1000 && $n%1000==0) || $n==$numRegistros){
          InfraDebug::getInstance()->gravar($n.' DE '.$numRegistros);
        }

        $rs = BancoSEI::getInstance()->consultarSql('select '.BancoSEI::getInstance()->formatarSelecaoStr(null,'sta_arquivamento',null).','.BancoSEI::getInstance()->formatarSelecaoNum(null,'id_localizador',null).' from protocolo where id_protocolo='.BancoSEI::getInstance()->formatarGravacaoDbl($objAtributoAndamentoDTO->getStrIdOrigem()));

        if (count($rs)) {

          $strStaArquivamento = BancoSEI::getInstance()->formatarLeituraStr($rs[0]['sta_arquivamento']);
          $numIdLocalizador = BancoSEI::getInstance()->formatarLeituraNum($rs[0]['id_localizador']);

          $objArquivamentoDTO = new ArquivamentoDTO();
          $objArquivamentoDTO->setDblIdProtocolo($objAtributoAndamentoDTO->getStrIdOrigem());
          $objArquivamentoDTO->setNumIdLocalizador($numIdLocalizador);

          if ($strStaArquivamento == ArquivamentoRN::$TA_NAO_ARQUIVADO) {
            $objArquivamentoDTO->setStrStaArquivamento(ArquivamentoRN::$TA_DESARQUIVADO);
          } else {
            $objArquivamentoDTO->setStrStaArquivamento($strStaArquivamento);
          }

          $objArquivamentoDTO->setNumIdAtividadeArquivamento($objAtributoAndamentoDTO->getNumIdAtividade());
          $objArquivamentoDTO->setNumIdAtividadeRecebimento(null);
          $objArquivamentoDTO->setNumIdAtividadeSolicitacao(null);
          $objArquivamentoDTO->setNumIdAtividadeDesarquivamento(null);
          $objArquivamentoBD->cadastrar($objArquivamentoDTO);
        }
      }

      unset($arrObjAtributoAndamentoDTO);

      InfraDebug::getInstance()->gravar('ATUALIZANDO DOCUMENTOS RECEBIDOS...');

      $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
      $objAtributoAndamentoDTO->retNumIdAtividade();
      $objAtributoAndamentoDTO->retStrIdOrigem();
      $objAtributoAndamentoDTO->setStrNome('DOCUMENTO');
      $objAtributoAndamentoDTO->setNumIdTarefaAtividade(TarefaRN::$TI_RECEBIMENTO_ARQUIVO);
      $objAtributoAndamentoDTO->setOrdNumIdAtividade(InfraDTO::$TIPO_ORDENACAO_ASC);

      $arrObjAtributoAndamentoDTO = InfraArray::indexarArrInfraDTO($objAtributoAndamentoRN->listarRN1367($objAtributoAndamentoDTO),'IdOrigem');

      $n = 0;
      $numRegistros = count($arrObjAtributoAndamentoDTO);

      foreach($arrObjAtributoAndamentoDTO as $objAtributoAndamentoDTO){

        if ((++$n >=1000 && $n%1000==0) || $n==$numRegistros){
          InfraDebug::getInstance()->gravar($n.' DE '.$numRegistros);
        }

        $rs = BancoSEI::getInstance()->consultarSql('select '.BancoSEI::getInstance()->formatarSelecaoStr(null,'sta_arquivamento',null).' from protocolo where id_protocolo='.BancoSEI::getInstance()->formatarGravacaoDbl($objAtributoAndamentoDTO->getStrIdOrigem()));

        if (count($rs)) {

          $strStaArquivamento = BancoSEI::getInstance()->formatarLeituraStr($rs[0]['sta_arquivamento']);

          $objArquivamentoDTO = new ArquivamentoDTO();
          $objArquivamentoDTO->retDblIdProtocolo();
          $objArquivamentoDTO->setDblIdProtocolo($objAtributoAndamentoDTO->getStrIdOrigem());
          $objArquivamentoDTO = $objArquivamentoRN->consultar($objArquivamentoDTO);

          if ($objArquivamentoDTO == null) {

            if ($strStaArquivamento == ArquivamentoRN::$TA_RECEBIDO) {
              $objArquivamentoDTO = new ArquivamentoDTO();
              $objArquivamentoDTO->setDblIdProtocolo($objAtributoAndamentoDTO->getStrIdOrigem());
              $objArquivamentoDTO->setStrStaArquivamento(ArquivamentoRN::$TA_RECEBIDO);
              $objArquivamentoDTO->setNumIdLocalizador(null);
              $objArquivamentoDTO->setNumIdAtividadeArquivamento(null);
              $objArquivamentoDTO->setNumIdAtividadeRecebimento($objAtributoAndamentoDTO->getNumIdAtividade());
              $objArquivamentoDTO->setNumIdAtividadeSolicitacao(null);
              $objArquivamentoDTO->setNumIdAtividadeDesarquivamento(null);
              $objArquivamentoBD->cadastrar($objArquivamentoDTO);
            }

          } else {

            $objArquivamentoDTO->setNumIdAtividadeRecebimento($objAtributoAndamentoDTO->getNumIdAtividade());
            $objArquivamentoBD->alterar($objArquivamentoDTO);

          }
        }
      }

      unset($arrObjAtributoAndamentoDTO);

      InfraDebug::getInstance()->gravar('ATUALIZANDO DOCUMENTOS COM SOLICITACAO DE DESARQUIVAMENTO...');

      $rs = BancoSEI::getInstance()->consultarSql('select '.BancoSEI::getInstance()->formatarSelecaoDbl(null,'id_protocolo',null).' from protocolo where sta_arquivamento='.BancoSEI::getInstance()->formatarGravacaoStr(ArquivamentoRN::$TA_SOLICITADO_DESARQUIVAMENTO));

      $n = 0;
      $numRegistros = count($rs);

      foreach($rs as $item){

        if ((++$n >=100 && $n%100==0) || $n==$numRegistros){
          InfraDebug::getInstance()->gravar($n);
        }

        $dblIdProtocolo = BancoSEI::getInstance()->formatarLeituraDbl($item['id_protocolo']);

        $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
        $objAtributoAndamentoDTO->retNumIdAtividade();
        $objAtributoAndamentoDTO->setStrIdOrigem($dblIdProtocolo);
        $objAtributoAndamentoDTO->setStrNome('DOCUMENTO');
        $objAtributoAndamentoDTO->setNumIdTarefaAtividade(TarefaRN::$TI_SOLICITADO_DESARQUIVAMENTO);
        $objAtributoAndamentoDTO->setNumMaxRegistrosRetorno(1);
        $objAtributoAndamentoDTO->setOrdNumIdAtividade(InfraDTO::$TIPO_ORDENACAO_DESC);

        $objAtributoAndamentoDTO = $objAtributoAndamentoRN->consultarRN1366($objAtributoAndamentoDTO);

        $objArquivamentoDTO = new ArquivamentoDTO();
        $objArquivamentoDTO->retDblIdProtocolo();
        $objArquivamentoDTO->setDblIdProtocolo($dblIdProtocolo);
        $objArquivamentoDTO = $objArquivamentoRN->consultar($objArquivamentoDTO);

        if ($objArquivamentoDTO != null) {
          $objArquivamentoDTO->setStrStaArquivamento(ArquivamentoRN::$TA_SOLICITADO_DESARQUIVAMENTO);
          $objArquivamentoDTO->setNumIdAtividadeSolicitacao($objAtributoAndamentoDTO->getNumIdAtividade());
          $objArquivamentoBD->alterar($objArquivamentoDTO);
        }
      }

      InfraDebug::getInstance()->gravar('ATUALIZANDO DOCUMENTOS COM DESARQUIVAMENTO...');

      $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
      $objAtributoAndamentoDTO->setDistinct(true);
      $objAtributoAndamentoDTO->retStrIdOrigem();
      $objAtributoAndamentoDTO->setStrNome('DOCUMENTO');
      $objAtributoAndamentoDTO->setNumIdTarefaAtividade(TarefaRN::$TI_DESARQUIVAMENTO);

      $arrIdOrigem = InfraArray::converterArrInfraDTO($objAtributoAndamentoRN->listarRN1367($objAtributoAndamentoDTO),'IdOrigem');

      $n = 0;
      $numRegistros = count($arrIdOrigem);

      foreach($arrIdOrigem as $strIdOrigem){

        if ((++$n >=100 && $n%100==0) || $n==$numRegistros){
          InfraDebug::getInstance()->gravar($n);
        }

        $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
        $objAtributoAndamentoDTO->retNumIdAtividade();
        $objAtributoAndamentoDTO->setStrIdOrigem($strIdOrigem);
        $objAtributoAndamentoDTO->setStrNome('DOCUMENTO');
        $objAtributoAndamentoDTO->setNumIdTarefaAtividade(TarefaRN::$TI_DESARQUIVAMENTO);
        $objAtributoAndamentoDTO->setNumMaxRegistrosRetorno(1);
        $objAtributoAndamentoDTO->setOrdNumIdAtividade(InfraDTO::$TIPO_ORDENACAO_DESC);

        $objAtributoAndamentoDTO = $objAtributoAndamentoRN->consultarRN1366($objAtributoAndamentoDTO);

        $objArquivamentoDTO = new ArquivamentoDTO();
        $objArquivamentoDTO->retDblIdProtocolo();
        $objArquivamentoDTO->setDblIdProtocolo($strIdOrigem);
        $objArquivamentoDTO = $objArquivamentoRN->consultar($objArquivamentoDTO);

        if ($objArquivamentoDTO != null) {
          $objArquivamentoDTO->setNumIdAtividadeDesarquivamento($objAtributoAndamentoDTO->getNumIdAtividade());
          $objArquivamentoBD->alterar($objArquivamentoDTO);
        }

      }

      InfraDebug::getInstance()->setBolDebugInfra(true);

    }catch(Exception $e){
      throw new InfraException('Erro atualizando dados de arquivamento.', $e);
    }
  }

  public function atualizarSequenciasMySql(){
    $arrSequencias = array(
      'seq_acesso',
      'seq_acesso_externo',
      'seq_acompanhamento',
      'seq_anexo',
      'seq_anotacao',
      'seq_arquivo_extensao',
      'seq_assinante',
      'seq_assinatura',
      'seq_assunto',
      'seq_atividade',
      'seq_atributo_andamento',
      'seq_base_conhecimento',
      'seq_bloco',
      'seq_cargo',
      'seq_cidade',
      'seq_conjunto_estilos',
      'seq_conjunto_estilos_item',
      'seq_contato',
      'seq_controle_interno',
      'seq_email_grupo_email',
      'seq_email_unidade',
      'seq_estilo',
      'seq_feed',
      'seq_feriado',
      'seq_grupo_acompanhamento',
      'seq_grupo_contato',
      'seq_grupo_email',
      'seq_grupo_protocolo_modelo',
      'seq_grupo_serie',
      'seq_hipotese_legal',
      'seq_imagem_formato',
      'seq_localizador',
      'seq_lugar_localizador',
      'seq_modelo',
      'seq_nivel_acesso_permitido',
      'seq_novidade',
      'seq_numeracao',
      'seq_observacao',
      'seq_operacao_servico',
      'seq_ordenador_despesa',
      'seq_pais',
      'seq_participante',
      'seq_protocolo_modelo',
      'seq_publicacao',
      'seq_rel_protocolo_protocolo',
      'seq_retorno_programado',
      'seq_secao_documento',
      'seq_secao_imprensa_nacional',
      'seq_secao_modelo',
      'seq_serie',
      'seq_serie_publicacao',
      'seq_servico',
      'seq_texto_padrao_interno',
      'seq_tipo_conferencia',
      'seq_tipo_contexto_contato',
      'seq_tipo_localizador',
      'seq_tipo_procedimento',
      'seq_tipo_suporte',
      'seq_tratamento',
      'seq_uf',
      'seq_unidade_publicacao',
      'seq_veiculo_imprensa_nacional',
      'seq_veiculo_publicacao',
      'seq_vocativo',
      'seq_grupo_unidade',
      'seq_email_utilizado',
      'seq_andamento_situacao',
      'seq_situacao',
      'seq_auditoria_protocolo',
      'seq_estatisticas',
      'seq_infra_auditoria',
      'seq_infra_log',
      'seq_infra_navegador',
      'seq_protocolo',
      'seq_versao_secao_documento',
      'seq_controle_unidade');

    foreach($arrSequencias as $strSequencia){

      if ($strSequencia=='seq_atributo_andamento_situaca'){
        $strIdOrigem = 'id_atributo_andamento_situacao';
        $strTabelaOrigem = 'atributo_andamento_situacao';
      }else{
        $strIdOrigem = str_replace('seq_','id_',$strSequencia);
        $strTabelaOrigem = str_replace('seq_','',$strSequencia);
      }

      $rsTab = BancoSEI::getInstance()->consultarSql('select max('.$strIdOrigem.') as ultimo from '.$strTabelaOrigem);

      if ($rsTab[0]['ultimo'] !== null){

        $rsSeq = BancoSEI::getInstance()->consultarSql('select max(id) as ultimo from '.$strSequencia);

        if ($rsSeq[0]['ultimo'] === null) {

          BancoSEI::getInstance()->executarSql('INSERT INTO ' . $strSequencia . ' (campo) VALUES (null)');

          $rsSeq = BancoSEI::getInstance()->consultarSql('select max(id) as ultimo from ' . $strSequencia);

        }

        if ($rsTab[0]['ultimo'] > $rsSeq[0]['ultimo']) {
          BancoSEI::getInstance()->executarSql('alter table ' . $strSequencia . ' AUTO_INCREMENT = ' . ($rsTab[0]['ultimo'] + 1));
        }
      }
    }
  }

  protected function fixAtividadeConclusaoAutomaticaUsuarioControlado(){

    BancoSEI::getInstance()->executarSql('update tarefa set nome=\'Conclusão Automática de Processo do Usuário @USUARIO@\' where id_tarefa='.TarefaRN::$TI_CONCLUSAO_AUTOMATICA_USUARIO);

    InfraDebug::getInstance()->setBolDebugInfra(false);

    InfraDebug::getInstance()->gravar('COMPLEMENTANDO ANDAMENTOS DE CONCLUSAO AUTOMATICA DO USUARIO...');

    $objAtividadeDTO = new AtividadeDTO();
    $objAtividadeDTO->retNumIdAtividade();
    $objAtividadeDTO->setNumIdTarefa(TarefaRN::$TI_CONCLUSAO_AUTOMATICA_USUARIO);

    $objAtividadeRN = new AtividadeRN();
    $arrObjAtividadeDTO = $objAtividadeRN->listarRN0036($objAtividadeDTO);

    $objAtributoAndamentoRN = new AtributoAndamentoRN();

    $numRegistros = count($arrObjAtividadeDTO);
    $n = 0;

    foreach($arrObjAtividadeDTO as $objAtividadeDTO){

      $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
      $objAtributoAndamentoDTO->retNumIdAtributoAndamento();
      $objAtributoAndamentoDTO->setStrNome('USUARIO');
      $objAtributoAndamentoDTO->setNumIdAtividade($objAtividadeDTO->getNumIdAtividade());

      if ($objAtributoAndamentoRN->consultarRN1366($objAtributoAndamentoDTO)==null) {

        $objAtributoAndamentoDTO->setStrValor(null);
        $objAtributoAndamentoDTO->setStrIdOrigem(null);

        $objAtributoAndamentoRN->cadastrarRN1363($objAtributoAndamentoDTO);
      }

      if ((++$n >= 100 && $n % 100 == 0) || $n == $numRegistros) {
        InfraDebug::getInstance()->gravar('ANDAMENTO: ' . $n . ' DE ' . $numRegistros);
      }
    }

    InfraDebug::getInstance()->setBolDebugInfra(true);
  }

  protected function fixIndexacaoObservacoesConectado(){

    InfraDebug::getInstance()->setBolDebugInfra(false);

    InfraDebug::getInstance()->gravar('INDEXANDO OBSERVACOES...');

    $objObservacaoBD = new ObservacaoBD(BancoSEI::getInstance());

    $objObservacaoDTO = new ObservacaoDTO();
    $objObservacaoDTO->retNumIdObservacao();
    $objObservacaoDTO->setStrDescricao(null,InfraDTO::$OPER_DIFERENTE);
    $objObservacaoDTO->setStrIdxObservacao(null);
    $arrIdObservacao = array_chunk(InfraArray::converterArrInfraDTO($objObservacaoBD->listar($objObservacaoDTO),'IdObservacao'),1000);

    $numRegistrosAtual = 0;
    foreach($arrIdObservacao as $arrIdObservacaoParte){

      $numRegistrosAtual += count($arrIdObservacaoParte);

      InfraDebug::getInstance()->gravar($numRegistrosAtual);

      $objObservacaoDTO = new ObservacaoDTO();
      $objObservacaoDTO->retNumIdObservacao();
      $objObservacaoDTO->retStrDescricao();
      $objObservacaoDTO->setNumIdObservacao($arrIdObservacaoParte, InfraDTO::$OPER_IN);
      $arrObjObservacaoDTO = $objObservacaoBD->listar($objObservacaoDTO);

      foreach ($arrObjObservacaoDTO as $objObservacaoDTO){
        $objObservacaoDTO->setStrIdxObservacao(InfraString::prepararIndexacao($objObservacaoDTO->getStrDescricao(),false));
        $objObservacaoBD->alterar($objObservacaoDTO);
      }
    }

    InfraDebug::getInstance()->setBolDebugInfra(true);
  }

  protected function fixIndexacaoOrgaosConectado(){

    InfraDebug::getInstance()->setBolDebugInfra(false);

    InfraDebug::getInstance()->gravar('INDEXANDO ORGAOS...');

    $objOrgaoDTO = new OrgaoDTO();
    $objOrgaoDTO->retNumIdOrgao();
    $objOrgaoDTO->retStrSigla();

    $objOrgaoRN = new OrgaoRN();
    $arrObjOrgaoDTO = $objOrgaoRN->listarRN1353($objOrgaoDTO);

    foreach($arrObjOrgaoDTO as $objOrgaoDTO){
      InfraDebug::getInstance()->gravar($objOrgaoDTO->getStrSigla());
      $objOrgaoRN->montarIndexacao($objOrgaoDTO);
    }

    InfraDebug::getInstance()->setBolDebugInfra(true);
  }

  protected function fixAcessoExternoConectado(){

    InfraDebug::getInstance()->setBolDebugInfra(false);

    InfraDebug::getInstance()->gravar('COMPLEMENTANDO ANDAMENTOS DE ACESSOS EXTERNOS...');

    $objAcessoExternoDTO = new AcessoExternoDTO();
    $objAcessoExternoDTO->setBolExclusaoLogica(false);
    $objAcessoExternoDTO->retNumIdAcessoExterno();
    $objAcessoExternoDTO->retNumIdAtividade();
    $objAcessoExternoDTO->retStrSinProcesso();
    $objAcessoExternoDTO->setStrStaTipo(AcessoExternoRN::$TA_ASSINATURA_EXTERNA);

    $objAcessoExternoRN = new AcessoExternoRN();
    $arrObjAcessoExternoDTO = $objAcessoExternoRN->listar($objAcessoExternoDTO);

    $numRegistros = count($arrObjAcessoExternoDTO);

    $objAtributoAndamentoBD = new AtributoAndamentoBD(BancoSEI::getInstance());

    $n = 0;
    foreach($arrObjAcessoExternoDTO as $objAcessoExternoDTO){

      $objAtributoAndamentoDTO = new AtributoAndamentoDTO();
      $objAtributoAndamentoDTO->setNumIdAtributoAndamento(null);
      $objAtributoAndamentoDTO->setNumIdAtividade($objAcessoExternoDTO->getNumIdAtividade());
      $objAtributoAndamentoDTO->setStrNome('VISUALIZACAO');
      $objAtributoAndamentoDTO->setStrValor(null);

      if ($objAcessoExternoDTO->getStrSinProcesso()=='S'){
        $objAtributoAndamentoDTO->setStrIdOrigem(AcessoExternoRN::$TV_INTEGRAL);
      }else{
        $objAtributoAndamentoDTO->setStrIdOrigem(AcessoExternoRN::$TV_NENHUM);
      }

      $objAtributoAndamentoBD->cadastrar($objAtributoAndamentoDTO);

      if ((++$n >= 100 && $n % 100 == 0) || $n == $numRegistros) {
        InfraDebug::getInstance()->gravar('ANDAMENTO: ' . $n . ' DE ' . $numRegistros);
      }
    }
    InfraDebug::getInstance()->setBolDebugInfra(true);
  }

  protected function fixProtocoloFormatadoConectado(){

    InfraDebug::getInstance()->setBolDebugInfra(false);

    InfraDebug::getInstance()->gravar('CORRIGINDO NUMERACAO DE PESQUISA DE PROCESSOS...');

    $objProtocoloDTO = new ProtocoloDTO();
    $objProtocoloDTO->retStrProtocoloFormatado();
    $objProtocoloDTO->retStrProtocoloFormatadoPesquisa();
    $objProtocoloDTO->retDblIdProtocolo();
    $objProtocoloDTO->setStrStaProtocolo(ProtocoloRN::$TP_PROCEDIMENTO);

    $objProtocoloRN = new ProtocoloRN();
    $arrObjProtocoloDTO = $objProtocoloRN->listarRN0668($objProtocoloDTO);

    $numRegistros = count($arrObjProtocoloDTO);

    $objProtocoloBD = new ProtocoloBD(BancoSEI::getInstance());
    $n = 0;
    foreach($arrObjProtocoloDTO as $objProtocoloDTO){

      $strProtocoloPesquisa = preg_replace("/[^0-9a-zA-Z]+/", '',$objProtocoloDTO->getStrProtocoloFormatado());

      if ($objProtocoloDTO->getStrProtocoloFormatadoPesquisa()!=$strProtocoloPesquisa){
        $dto = new ProtocoloDTO();
        $dto->setStrProtocoloFormatadoPesquisa($strProtocoloPesquisa);

        if ($objProtocoloBD->contar($dto)==0) {
          $dto->setDblIdProtocolo($objProtocoloDTO->getDblIdProtocolo());
          $objProtocoloBD->alterar($dto);
        }
      }

      if ((++$n >= 1000 && $n % 1000 == 0) || $n == $numRegistros) {
        InfraDebug::getInstance()->gravar($n . ' DE ' . $numRegistros);
      }
    }
    InfraDebug::getInstance()->setBolDebugInfra(true);
  }

  protected function fixIndexacaoContatosConectado(){

    InfraDebug::getInstance()->setBolDebugInfra(false);

    InfraDebug::getInstance()->gravar('REINDEXANDO CONTATOS...');

    $rs = BancoSEI::getInstance()->consultarSql('select id_contato from contato where cpf is not null or cnpj is not null');

    InfraDebug::getInstance()->setBolDebugInfra(false);

    $objContatoDTO = new ContatoDTO();
    $objContatoDTO->setNumIdContato(null);

    $objContatoRN = new ContatoRN();

    $numRegistros = count($rs);

    $n = 0;
    foreach($rs as $item){

      $objContatoDTO->setNumIdContato($item['id_contato']);

      $objContatoRN->montarIndexacaoRN0450($objContatoDTO);

      if ((++$n >= 1000 && $n % 1000 == 0) || $n == $numRegistros) {
        InfraDebug::getInstance()->gravar($n . ' DE ' . $numRegistros);
      }
    }

    InfraDebug::getInstance()->setBolDebugInfra(true);
  }

  protected function atualizarSequenciasControlado(){

    try{

      ini_set('max_execution_time','0');
      ini_set('mssql.timeout','0');

      InfraDebug::getInstance()->setBolLigado(true);
      InfraDebug::getInstance()->setBolDebugInfra(false);
      InfraDebug::getInstance()->setBolEcho(true);
      InfraDebug::getInstance()->limpar();

      $numSeg = InfraUtil::verificarTempoProcessamento();

      InfraDebug::getInstance()->gravar('Atualizar Sequencias - Iniciando...');

      $arrSequencias = array(
        'seq_acesso',
        'seq_acesso_externo',
        'seq_acompanhamento',
        'seq_anexo',
        'seq_anotacao',
        'seq_arquivo_extensao',
        'seq_assinante',
        'seq_assinatura',
        'seq_assunto',
        'seq_atividade',
        'seq_atributo',
        'seq_atributo_andamento',
        'seq_base_conhecimento',
        'seq_bloco',
        'seq_cargo',
        'seq_cidade',
        'seq_conjunto_estilos',
        'seq_conjunto_estilos_item',
        'seq_contato',
        'seq_controle_interno',
        'seq_dominio',
        'seq_email_grupo_email',
        'seq_email_unidade',
        'seq_estilo',
        'seq_feed',
        'seq_feriado',
        'seq_grupo_acompanhamento',
        'seq_grupo_contato',
        'seq_grupo_email',
        'seq_grupo_protocolo_modelo',
        'seq_grupo_serie',
        'seq_hipotese_legal',
        'seq_imagem_formato',
        'seq_localizador',
        'seq_lugar_localizador',
        'seq_modelo',
        'seq_nivel_acesso_permitido',
        'seq_novidade',
        'seq_numeracao',
        'seq_observacao',
        'seq_operacao_servico',
        'seq_ordenador_despesa',
        'seq_pais',
        'seq_participante',
        'seq_protocolo_modelo',
        'seq_publicacao',
        'seq_rel_protocolo_protocolo',
        'seq_retorno_programado',
        'seq_secao_documento',
        'seq_secao_imprensa_nacional',
        'seq_secao_modelo',
        'seq_serie',
        'seq_serie_publicacao',
        'seq_servico',
        'seq_texto_padrao_interno',
        'seq_tipo_conferencia',
        'seq_tipo_localizador',
        'seq_tipo_procedimento',
        'seq_tipo_suporte',
        'seq_tratamento',
        'seq_uf',
        'seq_unidade_publicacao',
        'seq_veiculo_imprensa_nacional',
        'seq_veiculo_publicacao',
        'seq_vocativo',
        'seq_grupo_unidade',
        'seq_email_utilizado',
        'seq_andamento_situacao',
        'seq_situacao',
        'seq_tarefa',
        'seq_email_sistema',
        'seq_tipo_formulario',
        'seq_tarja_assinatura',
        'seq_monitoramento_servico',
        'seq_tipo_contato',
        'seq_rel_unidade_tipo_contato',
        'seq_marcador',
        'seq_andamento_marcador',
        'seq_assunto_proxy',
        'seq_tabela_assuntos',
        'seq_serie_restricao',
        'seq_tipo_proced_restricao');

      foreach($arrSequencias as $strSequencia){

        if (BancoSEI::getInstance() instanceof InfraSqlServer || BancoSEI::getInstance() instanceof InfraMySql){
          BancoSEI::getInstance()->executarSql('drop table '.$strSequencia);
        }else{
          BancoSEI::getInstance()->executarSql('drop sequence '.$strSequencia);
        }

        $strIdOrigem = str_replace('seq_','id_',$strSequencia);
        $strTabelaOrigem = str_replace('seq_','',$strSequencia);

        $rs = BancoSEI::getInstance()->consultarSql('select max('.$strIdOrigem.') as ultimo from '.$strTabelaOrigem);

        if ($rs[0]['ultimo'] == null){
          $numInicial = 1;
        }else{
          $numInicial = $rs[0]['ultimo'] + 1;
        }

        BancoSEI::getInstance()->criarSequencialNativa($strSequencia, $numInicial);

        InfraDebug::getInstance()->gravar($strSequencia.': '.$numInicial);

      }

      $arrSequencias = array(
        'seq_auditoria_protocolo',
        'seq_estatisticas',
        'seq_infra_auditoria',
        'seq_infra_log',
        'seq_infra_navegador',
        'seq_protocolo',
        'seq_versao_secao_documento',
        'seq_controle_unidade',
        'seq_monitoramento_servico');

      foreach($arrSequencias as $strSequencia){

        if (BancoSEI::getInstance() instanceof InfraSqlServer || BancoSEI::getInstance() instanceof InfraMySql){
          BancoSEI::getInstance()->executarSql('drop table '.$strSequencia);
        }else{
          BancoSEI::getInstance()->executarSql('drop sequence '.$strSequencia);
        }

        $rs = BancoSEI::getInstance()->consultarSql('select '.BancoSEI::getInstance()->formatarSelecaoDbl(null,'max('.str_replace('seq_','id_',$strSequencia).')','ultimo').' from '.str_replace('seq_','',$strSequencia));

        if ($rs[0]['ultimo'] == null){
          $numInicial = 1;
        }else{
          $numInicial = $rs[0]['ultimo'] + 1;
        }

        if (BancoSEI::getInstance() instanceof InfraMySql){
          BancoSEI::getInstance()->executarSql('create table '.$strSequencia.' (id bigint not null primary key AUTO_INCREMENT, campo char(1) null) AUTO_INCREMENT = '.$numInicial);
        }else if (BancoSEI::getInstance() instanceof InfraSqlServer){
          BancoSEI::getInstance()->executarSql('create table '.$strSequencia.' (id bigint identity('.$numInicial.',1), campo char(1) null)');
        }else if (BancoSEI::getInstance() instanceof InfraOracle){
          BancoSEI::getInstance()->criarSequencialNativa($strSequencia, $numInicial);
        }

        InfraDebug::getInstance()->gravar($strSequencia.': '.$numInicial);
      }

      $numSeg = InfraUtil::verificarTempoProcessamento($numSeg);

      InfraDebug::getInstance()->gravar('Atualizar Sequencias - Finalizado em '.InfraData::formatarTimestamp($numSeg));

    }catch(Exception $e){
      throw new InfraException('Erro atualizando sequencias da base de dados.',$e);
    }
  }
}
?>
