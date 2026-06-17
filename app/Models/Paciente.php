<?php

namespace App\Models;

use PDO;
use Exception;

class Paciente
{
    private $pdo;
    private $clinica_id;

    public function __construct(PDO $pdo, int $clinica_id)
    {
        $this->pdo = $pdo;
        $this->clinica_id = $clinica_id;
    }

    /**
     * Busca todos os pacientes com paginação e filtro de busca.
     */
    public function getAll(int $limit, int $offset, string $busca = ''): array
    {
        $sql = "SELECT * FROM pacientes WHERE clinica_id = :clinica_id";
        if (!empty($busca)) {
            $sql .= " AND (nome LIKE :busca OR cpf LIKE :busca)";
        }
        $sql .= " ORDER BY nome ASC LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':clinica_id', $this->clinica_id, PDO::PARAM_INT);
        if (!empty($busca)) {
            $stmt->bindValue(':busca', "%$busca%", PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Conta o total de pacientes para fins de paginação.
     */
    public function getCount(string $busca = ''): int
    {
        $sql = "SELECT COUNT(id) FROM pacientes WHERE clinica_id = :clinica_id";
        if (!empty($busca)) {
            $sql .= " AND (nome LIKE :busca OR cpf LIKE :busca)";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':clinica_id', $this->clinica_id, PDO::PARAM_INT);
        if (!empty($busca)) {
            $stmt->bindValue(':busca', "%$busca%", PDO::PARAM_STR);
        }
        $stmt->execute();

        return (int)$stmt->fetchColumn();
    }

    /**
     * Busca um paciente pelo ID.
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT * FROM pacientes WHERE id = :id AND clinica_id = :clinica_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id, ':clinica_id' => $this->clinica_id]);
        $paciente = $stmt->fetch(PDO::FETCH_ASSOC);

        return $paciente ?: null;
    }

    /**
     * Salva (insere ou atualiza) um paciente.
     */
    public function save(array $data): bool
    {
        $id = $data['id'] ?? null;

        $params = [
            ':clinica_id' => $this->clinica_id,
            ':nome' => $data['nome'],
            ':cpf' => $data['cpf'] ?: null,
            ':data_nascimento' => $data['data_nascimento'] ?: null,
            ':email' => $data['email'] ?: null,
            ':telefone' => $data['telefone'] ?: null,
            ':cep' => $data['cep'] ?: null,
            ':endereco' => $data['endereco'] ?: null,
            ':numero' => $data['numero'] ?: null,
            ':bairro' => $data['bairro'] ?: null,
            ':cidade' => $data['cidade'] ?: null,
            ':estado' => $data['estado'] ?: null,
        ];

        if ($id) {
            $params[':id'] = $id;
            $sql = "UPDATE pacientes SET 
                        nome = :nome, cpf = :cpf, data_nascimento = :data_nascimento, email = :email, telefone = :telefone, 
                        cep = :cep, endereco = :endereco, numero = :numero, bairro = :bairro, cidade = :cidade, estado = :estado
                    WHERE id = :id AND clinica_id = :clinica_id";
        } else {
            $sql = "INSERT INTO pacientes (clinica_id, nome, cpf, data_nascimento, email, telefone, cep, endereco, numero, bairro, cidade, estado)
                    VALUES (:clinica_id, :nome, :cpf, :data_nascimento, :email, :telefone, :cep, :endereco, :numero, :bairro, :cidade, :estado)";
        }

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Cria rapidamente um paciente apenas com o nome (usado no Lançamento de Atendimento).
     * @return int O ID do paciente criado.
     * @throws Exception Se falhar na criação.
     */
    public function criar(string $nome): int
    {
        $sql = "INSERT INTO pacientes (nome, clinica_id) VALUES (?, ?)";
        $stmt = $this->pdo->prepare($sql);
        
        if ($stmt->execute([$nome, $this->clinica_id])) {
            return (int)$this->pdo->lastInsertId();
        }
        
        throw new Exception("Falha ao criar o paciente rapidamente.");
    }

    /**
     * Exclui um paciente, verificando se há atendimentos vinculados.
     */
    public function delete(int $id): bool
    {
        // Verifica se há atendimentos
        $stmtCheck = $this->pdo->prepare("SELECT COUNT(*) FROM atendimentos WHERE paciente_id = ? AND clinica_id = ?");
        $stmtCheck->execute([$id, $this->clinica_id]);
        if ((int)$stmtCheck->fetchColumn() > 0) {
            throw new Exception("Não é possível excluir o paciente, pois ele possui histórico de atendimentos.");
        }

        $stmtDelete = $this->pdo->prepare("DELETE FROM pacientes WHERE id = ? AND clinica_id = ?");
        return $stmtDelete->execute([$id, $this->clinica_id]);
    }

    /**
     * Busca rápida para AJAX (autocomplete).
     */
    public function search(string $term): array
    {
        $sql = "SELECT id, nome, cpf, telefone, email 
                FROM pacientes 
                WHERE clinica_id = :clinica_id 
                AND (nome LIKE :term OR cpf LIKE :term)
                ORDER BY nome ASC LIMIT 10";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':clinica_id' => $this->clinica_id,
            ':term' => "%$term%"
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca o histórico de procedimentos do paciente.
     */
    public function getHistorico(int $pacienteId): array
    {
        $sql = "SELECT
                    ap.id,
                    p.nome as procedimento_nome,
                    ap.local,
                    ap.descricao,
                    ap.status_execucao,
                    a.data_atendimento,
                    a.status_pagamento
                FROM atendimento_procedimentos ap
                JOIN atendimentos a ON ap.id_atendimento = a.id
                JOIN procedimentos p ON ap.id_procedimento = p.id
                WHERE a.paciente_id = :paciente_id 
                AND a.clinica_id = :clinica_id
                AND (ap.status_execucao = 'feito' OR ap.status_execucao = 'pendente')
                ORDER BY a.data_atendimento DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':paciente_id' => $pacienteId,
            ':clinica_id' => $this->clinica_id
        ]);

        $procedimentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $realizados = [];
        $pendentes = [];

        foreach ($procedimentos as $proc) {
            if ($proc['status_execucao'] === 'feito') {
                $realizados[] = $proc;
            } else {
                $pendentes[] = $proc;
            }
        }

        return ['realizados' => $realizados, 'pendentes' => $pendentes];
    }

    /**
     * Busca procedimentos pendentes do paciente.
     */
    public function getPendentes(int $pacienteId): array
    {
        $sql = "SELECT
                    ap.id as atendimento_procedimento_id,
                    p.nome as procedimento_nome,
                    p.categoria,
                    ap.local,
                    ap.descricao,
                    ap.valor_procedimento,
                    ap.quantidade,
                    ap.custo_auxiliar,
                    ap.natureza,
                    p.id as id_procedimento
                FROM atendimento_procedimentos ap
                JOIN atendimentos a ON ap.id_atendimento = a.id
                JOIN procedimentos p ON ap.id_procedimento = p.id
                WHERE a.paciente_id = :paciente_id 
                AND a.clinica_id = :clinica_id
                AND ap.status_execucao = 'pendente'
                ORDER BY a.data_atendimento DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':paciente_id' => $pacienteId,
            ':clinica_id' => $this->clinica_id
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca procedimentos pendentes do paciente.
     */
    public function findByName(string $name)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM pacientes WHERE clinica_id = ? AND LOWER(nome) LIKE LOWER(?) LIMIT 1");
        $stmt->execute([$this->clinica_id, '%' . $name . '%']);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getRelatorioProcedimentos(int $pacienteId, int $limit, int $offset)
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                ap.id as atendimento_procedimento_id,
                proc.nome as procedimento_nome,
                ap.local, 
                ap.descricao,
                a.data_atendimento,
                ap.status_execucao,
                a.status_pagamento,
                ap.url_arquivo
            FROM atendimento_procedimentos ap
            JOIN atendimentos a ON ap.id_atendimento = a.id
            JOIN procedimentos proc ON ap.id_procedimento = proc.id
            WHERE a.paciente_id = :paciente_id AND a.clinica_id = :clinica_id
            ORDER BY a.data_atendimento DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':paciente_id', $pacienteId, PDO::PARAM_INT);
        $stmt->bindValue(':clinica_id', $this->clinica_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countRelatorioProcedimentos(int $pacienteId)
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(ap.id)
            FROM atendimento_procedimentos ap
            JOIN atendimentos a ON ap.id_atendimento = a.id
            WHERE a.paciente_id = ? AND a.clinica_id = ?
        ");
        $stmt->execute([$pacienteId, $this->clinica_id]);
        return (int)$stmt->fetchColumn();
    }

    public function getOdontogramaStatus(int $pacienteId)
    {
        $stmt = $this->pdo->prepare("
            SELECT
                ap.local,
                ap.status_execucao
            FROM atendimento_procedimentos ap
            JOIN atendimentos a ON ap.id_atendimento = a.id
            WHERE a.paciente_id = ? AND a.clinica_id = ? AND ap.local IS NOT NULL AND ap.local != ''
        ");
        $stmt->execute([$pacienteId, $this->clinica_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $dente_status_raw = [];
        foreach ($rows as $proc) {
            $local = $proc['local'];
            if (!isset($dente_status_raw[$local])) {
                $dente_status_raw[$local] = ['pendente' => false, 'feito' => false];
            }
            if ($proc['status_execucao'] === 'feito') {
                $dente_status_raw[$local]['feito'] = true;
            }
            if ($proc['status_execucao'] === 'pendente' || $proc['status_execucao'] === 'finalizado') {
                $dente_status_raw[$local]['pendente'] = true;
            }
        }

        $dente_status_color = [];
        foreach ($dente_status_raw as $local => $statuses) {
            if ($statuses['feito'] && $statuses['pendente']) {
                $dente_status_color[$local] = 'yellow';
            } elseif ($statuses['feito']) {
                $dente_status_color[$local] = 'green';
            } elseif ($statuses['pendente']) {
                $dente_status_color[$local] = 'red';
            }
        }

        return $dente_status_color;
    }

    public function removerAnexo(int $idProcedimento)
    {
        // Primeiro busca o arquivo para deletar fisicamente
        $stmt = $this->pdo->prepare("
            SELECT url_arquivo FROM atendimento_procedimentos ap
            JOIN atendimentos a ON ap.id_atendimento = a.id
            WHERE ap.id = ? AND a.clinica_id = ?
        ");
        $stmt->execute([$idProcedimento, $this->clinica_id]);
        $arquivo = $stmt->fetchColumn();

        if ($arquivo) {
            $filePath = __DIR__ . '/../../public/' . $arquivo;
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        $stmt = $this->pdo->prepare("UPDATE atendimento_procedimentos SET url_arquivo = NULL WHERE id = ?");
        return $stmt->execute([$idProcedimento]);
    }

    public function removerProcedimento(int $idProcedimento)
    {
        // Verifica se o procedimento pertence à clínica e o status
        $stmt = $this->pdo->prepare("
            SELECT ap.status_execucao, a.status_pagamento, ap.url_arquivo
            FROM atendimento_procedimentos ap
            JOIN atendimentos a ON ap.id_atendimento = a.id
            WHERE ap.id = ? AND a.clinica_id = ?
        ");
        $stmt->execute([$idProcedimento, $this->clinica_id]);
        $proc = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$proc) {
            throw new Exception("Procedimento não encontrado.");
        }

        if (strtolower($proc['status_execucao']) !== 'pendente' || strtolower($proc['status_pagamento']) !== 'nao_aplicavel') {
            throw new Exception("Você não tem autorização para apagar este procedimento.");
        }

        // Se tiver arquivo, remove
        if ($proc['url_arquivo']) {
            $filePath = __DIR__ . '/../../public/' . $proc['url_arquivo'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        $stmt = $this->pdo->prepare("DELETE FROM atendimento_procedimentos WHERE id = ?");
        return $stmt->execute([$idProcedimento]);
    }

    public function salvarArquivo(int $idProcedimento, string $relativeUrl)
    {
        $stmt = $this->pdo->prepare("UPDATE atendimento_procedimentos SET url_arquivo = ? WHERE id = ?");
        return $stmt->execute([$relativeUrl, $idProcedimento]);
    }
}
