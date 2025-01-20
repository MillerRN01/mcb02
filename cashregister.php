<?php
class CashRegister {
    private $conn;
    private $userId;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->userId = $_SESSION['usuario_id'] ?? null;
    }
    
    // Obtém o saldo atual do caixa
    public function getCurrentBalance() {
        try {
            $sql = "SELECT 
                    COALESCE(SUM(CASE 
                        WHEN tipo = 'entrada' THEN valor 
                        WHEN tipo = 'saida' THEN -valor 
                    END), 0) as saldo
                    FROM transacoes_caixa 
                    WHERE DATE(data_hora) = CURDATE()";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            return (float) $row['saldo'];
        } catch (Exception $e) {
            error_log("Erro ao obter saldo: " . $e->getMessage());
            return 0;
        }
    }
    
    // Obtém as transações do dia
    public function getTodayTransactions() {
        try {
            $sql = "SELECT 
                    tc.*, 
                    u.nome as operador
                    FROM transacoes_caixa tc
                    LEFT JOIN usuarios u ON tc.usuario_id = u.id
                    WHERE DATE(tc.data_hora) = CURDATE()
                    ORDER BY tc.data_hora DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Erro ao obter transações: " . $e->getMessage());
            return [];
        }
    }
    
    // Verifica o status do caixa
    public function getCashierStatus() {
        try {
            $sql = "SELECT status FROM status_caixa WHERE data = CURDATE() ORDER BY id DESC LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                return $row['status'] === 'aberto';
            }
            return false;
        } catch (Exception $e) {
            error_log("Erro ao verificar status: " . $e->getMessage());
            return false;
        }
    }
    
    // Obtém o resumo diário
    public function getDailySummary() {
        try {
            $sql = "SELECT 
                    COALESCE(SUM(CASE WHEN tipo = 'entrada' THEN valor ELSE 0 END), 0) as income,
                    COALESCE(SUM(CASE WHEN tipo = 'saida' THEN valor ELSE 0 END), 0) as expense
                    FROM transacoes_caixa 
                    WHERE DATE(data_hora) = CURDATE()";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) {
            error_log("Erro ao obter resumo: " . $e->getMessage());
            return ['income' => 0, 'expense' => 0];
        }
    }
    
    // Registra uma nova transação
    public function registerTransaction($data) {
        try {
            if (!$this->getCashierStatus()) {
                throw new Exception("O caixa está fechado!");
            }
            
            $sql = "INSERT INTO transacoes_caixa (
                tipo, valor, descricao, metodo_pagamento, usuario_id, data_hora
            ) VALUES (?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param(
                "sdssi",
                $data['tipo'],
                $data['valor'],
                $data['descricao'],
                $data['metodo'],
                $this->userId
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Erro ao registrar transação");
            }
            
            return ['success' => true, 'message' => 'Transação registrada com sucesso'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    // Abre o caixa
    public function openCashier($valorInicial = 0) {
        try {
            if ($this->getCashierStatus()) {
                throw new Exception("O caixa já está aberto!");
            }
            
            $this->conn->begin_transaction();
            
            // Registra a abertura do caixa
            $sql1 = "INSERT INTO status_caixa (status, data, usuario_id) VALUES ('aberto', CURDATE(), ?)";
            $stmt1 = $this->conn->prepare($sql1);
            $stmt1->bind_param("i", $this->userId);
            $stmt1->execute();
            
            // Registra o valor inicial se houver
            if ($valorInicial > 0) {
                $sql2 = "INSERT INTO transacoes_caixa (
                    tipo, valor, descricao, metodo_pagamento, usuario_id, data_hora
                ) VALUES ('entrada', ?, 'Valor inicial do caixa', 'dinheiro', ?, NOW())";
                
                $stmt2 = $this->conn->prepare($sql2);
                $stmt2->bind_param("di", $valorInicial, $this->userId);
                $stmt2->execute();
            }
            
            $this->conn->commit();
            return ['success' => true, 'message' => 'Caixa aberto com sucesso'];
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    // Fecha o caixa
    public function closeCashier($observacoes = '') {
        try {
            if (!$this->getCashierStatus()) {
                throw new Exception("O caixa já está fechado!");
            }
            
            $this->conn->begin_transaction();
            
            // Registra o fechamento
            $sql = "INSERT INTO status_caixa (
                status, data, valor_final, observacoes, usuario_id
            ) VALUES ('fechado', CURDATE(), ?, ?, ?)";
            
            $valorFinal = $this->getCurrentBalance();
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("dsi", $valorFinal, $observacoes, $this->userId);
            $stmt->execute();
            
            $this->conn->commit();
            return ['success' => true, 'message' => 'Caixa fechado com sucesso'];
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    // Cancela uma transação
    public function cancelTransaction($transactionId) {
        try {
            if (!$this->getCashierStatus()) {
                throw new Exception("O caixa está fechado!");
            }
            
            $sql = "UPDATE transacoes_caixa SET 
                    cancelada = 1,
                    data_cancelamento = NOW(),
                    usuario_cancelamento_id = ?
                    WHERE id = ? AND DATE(data_hora) = CURDATE()";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $this->userId, $transactionId);
            
            if (!$stmt->execute()) {
                throw new Exception("Erro ao cancelar transação");
            }
            
            return ['success' => true, 'message' => 'Transação cancelada com sucesso'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}