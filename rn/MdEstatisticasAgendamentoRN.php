<?
require_once dirname(__FILE__) . '/../../../SEI.php';

class MdEstatisticasAgendamentoRN extends InfraRN
{

    protected function inicializarObjInfraIBanco() {
        return BancoSEI::getInstance();
    }

    public function coletarIndicadores() {
        InfraDebug::getInstance()->setBolLigado(true);
        InfraDebug::getInstance()->setBolDebugInfra(false);
        InfraDebug::getInstance()->setBolEcho(false);
        InfraDebug::getInstance()->limpar();

        try {
            $enviar = new MdEstatisticasEnviarRN();

            $this->gravarLog('Autenticar no WebService');
            if (!$enviar->autenticar()) {
                throw new InfraException('Problemas com a autenticação.');
            }

            $this->gravarLog('Autenticado. Coletando indicadores');

            $coletor = new MdEstatisticasColetarRN();
            $indicadores = $coletor->coletarIndicadores();

            $this->gravarLog('Indicadores coletados, enviando');

            $saida = $enviar->enviarIndicadores($indicadores);

            $id = $saida['id'];

            if (!$id) {
                throw new InfraException('Erro no envio dos indicadores.');
            }

            $this->gravarLog('Indicadores recebidos. Coletar indicadores do tipo lista');

            $this->gravarLog('Obter a data do último envio das quantidades de acessos ');
            $data = $enviar->obterUltimoAcesso();
            $this->gravarLog('Ultima data das quantidades de acessos: ' . $data . '. Coletar quantidade de acessos');

            $acessos = $coletor->obterAcessosUsuarios($data);
            $this->gravarLog('Coletado. Enviar quantidade de acessos: ');
            $enviar->enviarAcessos($acessos, $id);

            $this->gravarLog('Enviado. Coletar velocidades por cidade: ');
            $velocidades = $coletor->obterVelocidadePorCidade();
            $this->gravarLog('Coletado. Enviar: ');
            $enviar->enviarVelocidades($velocidades, $id);

            $this->gravarLog('Enviado. Coletar os sistemas operacionais dos usuários: ');
            $sistemasOperacionaisUsuarios = $coletor->obterSistemasOperacionaisUsuarios();
            $this->gravarLog('Coletado. Enviar: ');
            $enviar->enviarSistemasUsuarios($sistemasOperacionaisUsuarios, $id);

            $this->gravarLog('Enviado. Coletar os navegadores: ');
            $navegadores = $coletor->obterNavegadores();
            $this->gravarLog('Coletado. Enviar: ');
            $enviar->enviarNavegadores($navegadores, $id);

            $this->gravarLog('Enviado. Coletar a quantidade de logs de erro: ');
            $logs = $coletor->obterQuantidadeLogErro();
            $this->gravarLog('Coletado. Enviar: ');
            $enviar->enviarLogsErro($logs, $id);

            $this->gravarLog('Enviado. Obter a ultima data que foi enviado a quantidade de recursos ');
            $dataultimorecurso = $enviar->obterUltimoRecurso();
            $this->gravarLog('Ultima data das quantidades de recursos: ' . $dataultimorecurso . '. Coletar quantidade de recursos');
            $recursos = $coletor->obterQuantidadeRecursos($dataultimorecurso);
            $this->gravarLog('Coletado. Enviar: ');
            $enviar->enviarRecursos($recursos, $id);
            $this->gravarLog('Enviado: ');

            $this->gravarLog('Finalizado');

            LogSEI::getInstance()->gravar(InfraDebug::getInstance()->getStrDebug(), InfraLog::$INFORMACAO);
        } catch (Exception $e) {
            InfraDebug::getInstance()->setBolLigado(false);
            InfraDebug::getInstance()->setBolDebugInfra(false);
            InfraDebug::getInstance()->setBolEcho(false);
            throw new InfraException('Erro processando estatísticas do sistema.', $e);
        }

    }

    private function gravarLog($texto) {
        InfraDebug::getInstance()->gravar($texto, InfraLog::$INFORMACAO);
    }
}
?>
