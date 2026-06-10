<?php
namespace App\Models;

use PDO;
use Exception;

class Atendimento
{
    private $pdo;
    private $clinicaId;

    public function __construct(PDO $pdo, int $clinicaId)
    {
        $this->pdo = $pdo;
        $this->clinicaId = $clinicaId;
    }

    /**
     * Calcula o faturamento bruto mensal para fins de regra de comissão.
     * Isola por clínica.
     */
    public function getFaturamentoBrutoMensal(string $dataInicio, string $dataFim): float
    {
        $stmt = $this->pdo->prepare("
            SELECT SUM(ap.valor_procedimento) as total
            FROM atendimento_procedimentos ap
            JOIN atendimentos a ON ap.id_atendimento = a.id
            WHERE a.data_atendimento BETWEEN ? AND ?
            AND a.clinica_id = ?
        ");
        $stmt->execute([$dataInicio, $dataFim, $this->clinicaId]);
        return (float) ($stmt->fetchColumn() ?: 0.0);
    }

    /**
     * Deleta procedimentos pendentes que foram finalizados na sessão atual.
     */
    public function deletarProcedimentosPendentes(array $ids): void
    {
        // Filtra apenas IDs numéricos por segurança
        $idsSeguros = array_filter($ids, 'is_numeric');
        
        if (empty($idsSeguros)) {
            return;
        }

        // Para garantir que os procedimentos pertencem à clínica correta,
        // precisamos fazer um JOIN com a tabela de atendimentos na deleção.
        $inQuery = implode(',', array_fill(0, count($idsSeguros), '?'));
        
        // MariaDB suporta DELETE com JOIN
        $sql = "
            DELETE ap FROM atendimento_procedimentos ap
            JOIN atendimentos a ON ap.id_atendimento = a.id
            WHERE ap.id IN ($inQuery) AND a.clinica_id = ?
        ";
        
        $params = array_merge($idsSeguros, [$this->clinicaId]);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }

    /**
     * Insere o registro mestre de atendimento e retorna seu ID.
     */
    public function criarAtendimento(array $dados): int
    {
        $sql = "INSERT INTO atendimentos 
                (clinica_id, paciente_id, id_dentista, data_atendimento, valor_total, comissao_dentista, custo_auxiliar, valor_liquido_clinica, status_pagamento, url_arquivo) 
                VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $this->clinicaId,
            $dados['paciente_id'],
            $dados['id_dentista'],
            $dados['valor_total'] ?? 0.0,
            $dados['comissao_dentista'] ?? 0.0,
            $dados['custo_auxiliar'] ?? 0.0,
            $dados['valor_liquido_clinica'] ?? 0.0,
            $dados['status_pagamento'] ?? 'nao_aplicavel',
            $dados['url_arquivo'] ?? null
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Insere os procedimentos vinculados a um atendimento.
     */
    public function criarProcedimentosAtendimento(int $idAtendimento, array $procedimentos): void
    {
        $sql = "INSERT INTO atendimento_procedimentos 
                (clinica_id, id_atendimento, id_procedimento, quantidade, valor_procedimento, custo_auxiliar, local, descricao, status_execucao, natureza) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->pdo->prepare($sql);

        foreach ($procedimentos as $proc) {
            $stmt->execute([
                $this->clinicaId,
                $idAtendimento,
                $proc['id'],
                $proc['quantidade'] ?? 1,
                $proc['valor_total'] ?? 0.0,
                $proc['custo_auxiliar_manual'] ?? 0.0,
                $proc['local'] ?? null,
                $proc['descricao'] ?? null,
                $proc['status_execucao'] ?? 'pendente',
                $proc['natureza'] ?? null
            ]);
        }
    }

    /**
     * Busca o ID do último atendimento pendente de um paciente.
     */
    public function buscarUltimoPendente(int $pacienteId): ?int
    {
        $stmt = $this->pdo->prepare("
            SELECT id FROM atendimentos 
            WHERE paciente_id = ? AND clinica_id = ? AND status_pagamento = 'pendente' 
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute([$pacienteId, $this->clinicaId]);
        $id = $stmt->fetchColumn();
        return $id ? (int)$id : null;
    }

    /**
     * Busca procedimentos finalizados vinculados a um atendimento.
     */
    public function buscarProcedimentosFinalizados(int $atendimentoId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT ap.id, p.nome, ap.valor_procedimento, ap.quantidade
            FROM atendimento_procedimentos ap
            JOIN procedimentos p ON ap.id_procedimento = p.id
            WHERE ap.id_atendimento = ? AND ap.clinica_id = ? AND ap.status_execucao = 'finalizado'
        ");
        $stmt->execute([$atendimentoId, $this->clinicaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Calcula o faturamento bruto total de atendimentos pagos no período para a clínica atual.
     */
    public function obterFaturamentoBrutoPeriodo(string $dataInicio, string $dataFim): float
    {
        $stmt = $this->pdo->prepare("
            SELECT SUM(ap.valor_procedimento) 
            FROM atendimentos a 
            JOIN atendimento_procedimentos ap ON a.id = ap.id_atendimento
            WHERE a.data_atendimento BETWEEN ? AND ? 
            AND a.status_pagamento = 'pago' 
            AND ap.status_execucao IN ('feito', 'finalizado')
            AND a.clinica_id = ?
        ");
        $stmt->execute([$dataInicio, $dataFim, $this->clinicaId]);
        return (float) ($stmt->fetchColumn() ?: 0.0);
    }

    /**
     * Calcula o faturamento líquido total da clínica no período.
     */
    public function obterFaturamentoLiquidoPeriodo(string $dataInicio, string $dataFim): float
    {
        $stmt = $this->pdo->prepare("
            SELECT SUM(valor_liquido_clinica) 
            FROM atendimentos 
            WHERE data_atendimento BETWEEN ? AND ? 
            AND status_pagamento = 'pago'
            AND clinica_id = ?
        ");
        $stmt->execute([$dataInicio, $dataFim, $this->clinicaId]);
        return (float) ($stmt->fetchColumn() ?: 0.0);
    }

    /**
     * Conta atendimentos pagos no período para paginação.
     */
    public function obterContagemPeriodo(string $dataInicio, string $dataFim): int
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(DISTINCT a.id) 
            FROM atendimentos a 
            WHERE a.data_atendimento BETWEEN ? AND ? 
            AND a.status_pagamento = 'pago'
            AND a.clinica_id = ?
        ");
        $stmt->execute([$dataInicio, $dataFim, $this->clinicaId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Lista atendimentos pagos no período com paginação.
     */
    public function listarPeriodoPaginado(string $dataInicio, string $dataFim, int $limit, int $offset): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                a.id, a.data_atendimento, p.nome as paciente_nome, a.valor_liquido_clinica, 
                u.nome as dentista, 
                GROUP_CONCAT(CASE WHEN ap.status_execucao IN ('feito', 'finalizado') THEN proc.nome END SEPARATOR ', ') as procedimento, 
                SUM(CASE WHEN ap.status_execucao IN ('feito', 'finalizado') THEN ap.valor_procedimento ELSE 0 END) as valor_bruto 
            FROM atendimentos a 
            JOIN pacientes p ON a.paciente_id = p.id
            JOIN usuarios u ON a.id_dentista = u.id 
            LEFT JOIN atendimento_procedimentos ap ON a.id = ap.id_atendimento 
            LEFT JOIN procedimentos proc ON ap.id_procedimento = proc.id 
            WHERE a.data_atendimento BETWEEN :data_inicio AND :data_fim 
            AND a.status_pagamento = 'pago'
            AND a.clinica_id = :clinica_id
            GROUP BY a.id
            ORDER BY a.data_atendimento DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':data_inicio', $data_inicio);
        $stmt->bindValue(':data_fim', $data_fim);
        $stmt->bindValue(':clinica_id', $this->clinicaId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Relatório diário: faturamento bruto do dia.
     */
    public function obterFaturamentoBrutoDiario(string $data): float
    {
        $stmt = $this->pdo->prepare("
            SELECT SUM(ap.valor_procedimento) as total 
            FROM atendimentos a
            JOIN atendimento_procedimentos ap ON a.id = ap.id_atendimento
            WHERE DATE(a.data_atendimento) = ? 
            AND a.status_pagamento = 'pago' 
            AND ap.status_execucao IN ('feito', 'finalizado')
            AND a.clinica_id = ?
        ");
        $stmt->execute([$data, $this->clinicaId]);
        return (float) ($stmt->fetchColumn() ?: 0.0);
    }

    /**
     * Relatório diário: total de taxas de cartão aplicadas no dia.
     */
    public function obterTaxasCartaoDiario(string $data): float
    {
        $stmt = $this->pdo->prepare("
            SELECT SUM(a.taxa_cartao) as total 
            FROM atendimentos a 
            WHERE DATE(a.data_atendimento) = ? 
            AND a.status_pagamento = 'pago' 
            AND a.clinica_id = ?
            AND EXISTS (
                SELECT 1 
                FROM atendimento_procedimentos ap 
                WHERE ap.id_atendimento = a.id 
                AND ap.status_execucao IN ('feito', 'finalizado')
            )
        ");
        $stmt->execute([$data, $this->clinicaId]);
        return (float) ($stmt->fetchColumn() ?: 0.0);
    }

    /**
     * Relatório diário: custo com auxiliar/protético no dia.
     */
    public function obterCustoAuxiliarDiario(string $data): float
    {
        $stmt = $this->pdo->prepare("
            SELECT SUM(a.custo_auxiliar) as total 
            FROM atendimentos a 
            WHERE DATE(a.data_atendimento) = ? 
            AND a.status_pagamento = 'pago' 
            AND a.clinica_id = ?
            AND EXISTS (
                SELECT 1 
                FROM atendimento_procedimentos ap 
                WHERE ap.id_atendimento = a.id 
                AND ap.status_execucao IN ('feito', 'finalizado')
            )
        ");
        $stmt->execute([$data, $this->clinicaId]);
        return (float) ($stmt->fetchColumn() ?: 0.0);
    }

    /**
     * Relatório diário: comissões pagas para dentistas no dia.
     */
    public function obterComissoesDentistasDiario(string $data): array
    {
        $stmt = $this->pdo->prepare("
            SELECT u.nome, SUM(a.comissao_dentista) as total_comissao
            FROM atendimentos a
            JOIN usuarios u ON a.id_dentista = u.id
            WHERE DATE(a.data_atendimento) = ? 
            AND a.status_pagamento = 'pago' 
            AND a.clinica_id = ?
            AND EXISTS (
                SELECT 1 
                FROM atendimento_procedimentos ap 
                WHERE ap.id_atendimento = a.id 
                AND ap.status_execucao IN ('feito', 'finalizado')
            )
            GROUP BY u.nome
            HAVING total_comissao > 0
            ORDER BY u.nome
        ");
        $stmt->execute([$data, $this->clinicaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Relatório de dentistas: faturamento e comissões por dentista no período.
     */
    public function obterFaturamentoPorDentistaPeriodo(string $dataInicio, string $dataFim, string $dentistaId = 'todos'): array
    {
        $sql = "
            SELECT
                u.id as dentista_id,
                u.nome as dentista_nome,
                COUNT(atendimento_agg.id) as total_atendimentos,
                SUM(atendimento_agg.faturamento_bruto) as faturamento_bruto,
                SUM(atendimento_agg.valor_liquido_clinica) as valor_para_clinica,
                SUM(atendimento_agg.comissao_dentista) as valor_para_dentista
            FROM usuarios u
            JOIN (
                SELECT
                    a.id_dentista,
                    a.id,
                    a.valor_liquido_clinica,
                    a.comissao_dentista,
                    SUM(ap.valor_procedimento) as faturamento_bruto
                FROM atendimentos a
                JOIN atendimento_procedimentos ap ON a.id = ap.id_atendimento
                WHERE a.data_atendimento BETWEEN :data_inicio AND :data_fim
                  AND ap.status_execucao IN ('finalizado', 'feito')
                  AND a.status_pagamento = 'pago'
                  AND a.clinica_id = :clinica_id
                GROUP BY a.id, a.id_dentista, a.valor_liquido_clinica, a.comissao_dentista
            ) as atendimento_agg ON u.id = atendimento_agg.id_dentista
            WHERE u.clinica_id = :clinica_id
        ";

        $params = [
            'data_inicio' => $dataInicio,
            'data_fim' => $dataFim,
            'clinica_id' => $this->clinicaId
        ];

        if ($dentistaId !== 'todos') {
            $sql .= " AND u.id = :dentista_id";
            $params['dentista_id'] = (int)$dentistaId;
        }

        $sql .= " GROUP BY u.id, u.nome ORDER BY faturamento_bruto DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Relatório por procedimentos: contagem e representação.
     */
    public function obterRelatorioProcedimentosPeriodo(string $dataInicio, string $dataFim): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                p.nome as procedimento_nome,
                SUM(ap.quantidade) as quantidade_executada,
                SUM(ap.valor_procedimento) as valor_bruto_total
            FROM atendimento_procedimentos ap
            JOIN atendimentos a ON ap.id_atendimento = a.id
            JOIN procedimentos p ON ap.id_procedimento = p.id
            WHERE a.data_atendimento BETWEEN ? AND ? 
            AND ap.status_execucao IN ('feito', 'finalizado')
            AND a.status_pagamento = 'pago'
            AND a.clinica_id = ?
            GROUP BY p.id, p.nome
            ORDER BY quantidade_executada DESC, valor_bruto_total DESC
        ");
        $stmt->execute([$dataInicio, $dataFim, $this->clinicaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Relatório por procedimentos: total de quantidades executadas no período.
     */
    public function obterTotalProcedimentosQuantidadePeriodo(string $dataInicio, string $dataFim): int
    {
        $stmt = $this->pdo->prepare("
            SELECT SUM(ap.quantidade) 
            FROM atendimento_procedimentos ap
            JOIN atendimentos a ON ap.id_atendimento = a.id
            WHERE a.data_atendimento BETWEEN ? AND ? 
            AND ap.status_execucao IN ('feito', 'finalizado')
            AND a.status_pagamento = 'pago'
            AND a.clinica_id = ?
        ");
        $stmt->execute([$dataInicio, $dataFim, $this->clinicaId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Obtém dados consolidados de faturamento e despesas por dia no período (SaaS)
     */
    public function obterDadosGraficoFaturamentoEDespesas(string $dataInicio, string $dataFim): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                dia,
                SUM(faturamento) as faturamento,
                SUM(despesa) as despesa
            FROM (
                SELECT
                    DATE(a.data_atendimento) as dia,
                    SUM(ap.valor_procedimento) as faturamento,
                    0 as despesa
                FROM atendimentos a
                JOIN atendimento_procedimentos ap ON a.id = ap.id_atendimento
                WHERE a.data_atendimento BETWEEN ? AND ? 
                AND a.status_pagamento = 'pago' 
                AND ap.status_execucao IN ('feito', 'finalizado')
                AND a.clinica_id = ?
                GROUP BY DATE(a.data_atendimento)

                UNION ALL

                SELECT
                    DATE(data_despesa) as dia,
                    0 as faturamento,
                    SUM(valor) as despesa
                FROM despesas
                WHERE data_despesa BETWEEN ? AND ?
                AND clinica_id = ?
                GROUP BY DATE(data_despesa)
            ) as T
            GROUP BY dia
            ORDER BY dia
        ");
        $stmt->execute([
            $dataInicio, $dataFim, $this->clinicaId,
            $dataInicio, $dataFim, $this->clinicaId
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtém faturamento líquido consolidado da clínica por dia no período (SaaS)
     */
    public function obterDadosGraficoLiquido(string $dataInicio, string $dataFim): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                DATE(data_atendimento) as dia,
                SUM(valor_liquido_clinica) as liquido
            FROM atendimentos
            WHERE data_atendimento BETWEEN ? AND ? 
            AND status_pagamento = 'pago'
            AND clinica_id = ?
            GROUP BY DATE(data_atendimento)
            ORDER BY dia
        ");
        $stmt->execute([$dataInicio, $dataFim, $this->clinicaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtém representação das formas de pagamento no período (SaaS)
     */
    public function obterDadosGraficoPagamentos(string $dataInicio, string $dataFim): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                ap.forma_pagamento,
                SUM(ap.valor) as total
            FROM
                atendimento_pagamentos ap
            JOIN
                atendimentos a ON ap.id_atendimento = a.id
            WHERE
                a.data_atendimento BETWEEN ? AND ?
                AND a.clinica_id = ?
                AND ap.clinica_id = ?
            GROUP BY
                ap.forma_pagamento
        ");
        $stmt->execute([$dataInicio, $dataFim, $this->clinicaId, $this->clinicaId]);
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * Conta atendimentos pagos para a clínica atual (Dashboard com busca)
     */
    public function obterContagemDashboard(string $busca = ''): int
    {
        $sql = "
            SELECT COUNT(DISTINCT a.id) 
            FROM atendimentos a 
            JOIN pacientes p ON a.paciente_id = p.id
            WHERE a.status_pagamento = 'pago' AND a.clinica_id = ?
        ";
        
        $params = [$this->clinicaId];
        if (!empty($busca)) {
            $sql .= " AND p.nome LIKE ?";
            $params[] = "%$busca%";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Lista atendimentos pagos no Dashboard com paginação e busca por paciente (SaaS)
     */
    public function listarDashboard(string $busca, int $limit, int $offset): array
    {
        $sql = "
            SELECT 
                a.id,
                a.data_atendimento,
                p.nome AS paciente_nome,
                a.status_pagamento,
                a.taxa_cartao,
                a.valor_liquido_clinica,
                a.custo_auxiliar,
                a.comissao_dentista,
                a.url_arquivo,
                u.nome AS dentista,
                SUM(CASE 
                    WHEN ap.status_execucao IN ('feito', 'finalizado')
                    THEN ap.valor_procedimento 
                    ELSE 0 
                END) AS valor_bruto_total,
                GROUP_CONCAT(
                    CASE 
                        WHEN ap.status_execucao IN ('feito', 'finalizado')
                        THEN proc.nome 
                    END 
                    SEPARATOR ', '
                ) AS procedimentos
            FROM atendimentos a
            JOIN pacientes p ON a.paciente_id = p.id
            JOIN usuarios u ON a.id_dentista = u.id
            LEFT JOIN atendimento_procedimentos ap ON a.id = ap.id_atendimento
            LEFT JOIN procedimentos proc ON ap.id_procedimento = proc.id
            WHERE a.status_pagamento = 'pago' AND a.clinica_id = :clinica_id
        ";

        if (!empty($busca)) {
            $sql .= " AND p.nome LIKE :busca";
        }

        $sql .= "
            GROUP BY a.id
            ORDER BY a.data_atendimento DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':clinica_id', $this->clinicaId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        if (!empty($busca)) {
            $stmt->bindValue(':busca', "%$busca%", PDO::PARAM_STR);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

