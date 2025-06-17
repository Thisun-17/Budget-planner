<?php
require_once 'config/database.php';

// Get current month for default
$current_month = date('Y-m');

// Get budget for current month
$budget_stmt = $pdo->prepare("SELECT * FROM budget WHERE month = ?");
$budget_stmt->execute([$current_month]);
$budget = $budget_stmt->fetch(PDO::FETCH_ASSOC);

// Get total expenses for current month
$expenses_stmt = $pdo->prepare("
    SELECT SUM(amount) as total 
    FROM expenses 
    WHERE DATE_FORMAT(expense_date, '%Y-%m') = ?
");
$expenses_stmt->execute([$current_month]);
$total_expenses = $expenses_stmt->fetchColumn() ?: 0;

// Calculate remaining budget
$budget_amount = $budget ? $budget['amount'] : 0;
$remaining = $budget_amount - $total_expenses;
$percentage_used = $budget_amount > 0 ? ($total_expenses / $budget_amount) * 100 : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget Planner</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .header {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        
        .budget-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .budget-card {
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            color: white;
        }
        
        .budget-total { background: #4CAF50; }
        .budget-spent { background: #2196F3; }
        .budget-remaining { 
            background: <?php 
                if ($percentage_used >= 100) echo '#f44336';  // Red if over budget
                elseif ($percentage_used >= 80) echo '#ff9800'; // Orange if close
                else echo '#4CAF50'; // Green if safe
            ?>;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        .btn {
            background: #007bff;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .btn:hover {
            background: #0056b3;
        }
        
        .warning {
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
            font-weight: bold;
        }
        
        .warning.danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .warning.caution {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .expenses-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .expenses-table th, .expenses-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .expenses-table th {
            background: #f8f9fa;
            font-weight: bold;
        }
        
        .delete-btn {
            background: #dc3545;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="header">💰 Budget Planner</h1>
        
        <!-- Budget Overview -->
        <div class="budget-overview">
            <div class="budget-card budget-total">
                <h3>Monthly Budget</h3>
                <h2>$<?php echo number_format($budget_amount, 2); ?></h2>
            </div>
            <div class="budget-card budget-spent">
                <h3>Total Spent</h3>
                <h2>$<?php echo number_format($total_expenses, 2); ?></h2>
            </div>
            <div class="budget-card budget-remaining">
                <h3>Remaining</h3>
                <h2>$<?php echo number_format($remaining, 2); ?></h2>
            </div>
        </div>
        
        <!-- Budget Warnings -->
        <?php if ($percentage_used >= 100): ?>
            <div class="warning danger">
                ⚠️ You've exceeded your budget by $<?php echo number_format($total_expenses - $budget_amount, 2); ?>!
            </div>
        <?php elseif ($percentage_used >= 80): ?>
            <div class="warning caution">
                ⚠️ You've used <?php echo round($percentage_used); ?>% of your budget. Be careful!
            </div>
        <?php endif; ?>
        
        <!-- Set Budget Form -->
        <h3>Set Monthly Budget</h3>
        <form method="POST" action="actions/set_budget.php">
            <div class="form-group">
                <label>Month:</label>
                <input type="month" name="month" value="<?php echo $current_month; ?>" required>
            </div>
            <div class="form-group">
                <label>Budget Amount:</label>
                <input type="number" name="amount" step="0.01" min="0" 
                       value="<?php echo $budget ? $budget['amount'] : ''; ?>" 
                       placeholder="Enter budget amount" required>
            </div>
            <button type="submit" class="btn">Set Budget</button>
        </form>
    </div>
    
    <div class="container">
        <!-- Add Expense Form -->
        <h3>Add Expense</h3>
        <form method="POST" action="actions/add_expense.php">
            <div class="form-group">
                <label>Description:</label>
                <input type="text" name="description" placeholder="What did you spend on?" required>
            </div>
            <div class="form-group">
                <label>Amount:</label>
                <input type="number" name="amount" step="0.01" min="0.01" placeholder="0.00" required>
            </div>
            <div class="form-group">
                <label>Category:</label>
                <select name="category">
                    <option value="Food">Food</option>
                    <option value="Transportation">Transportation</option>
                    <option value="Entertainment">Entertainment</option>
                    <option value="Bills">Bills</option>
                    <option value="Shopping">Shopping</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="form-group">
                <label>Date:</label>
                <input type="date" name="expense_date" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <button type="submit" class="btn">Add Expense</button>
        </form>
    </div>
    
    <div class="container">
        <!-- Recent Expenses -->
        <h3>Recent Expenses</h3>
        <?php
        // Get recent expenses
        $recent_stmt = $pdo->prepare("
            SELECT * FROM expenses 
            WHERE DATE_FORMAT(expense_date, '%Y-%m') = ? 
            ORDER BY expense_date DESC, created_at DESC 
            LIMIT 10
        ");
        $recent_stmt->execute([$current_month]);
        $recent_expenses = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        
        <?php if (count($recent_expenses) > 0): ?>
            <table class="expenses-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Category</th>
                        <th>Amount</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_expenses as $expense): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($expense['expense_date'])); ?></td>
                            <td><?php echo htmlspecialchars($expense['description']); ?></td>
                            <td><?php echo htmlspecialchars($expense['category']); ?></td>
                            <td>$<?php echo number_format($expense['amount'], 2); ?></td>
                            <td>
                                <form method="POST" action="actions/delete_expense.php" style="display: inline;">
                                    <input type="hidden" name="expense_id" value="<?php echo $expense['id']; ?>">
                                    <button type="submit" class="delete-btn" 
                                            onclick="return confirm('Delete this expense?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No expenses recorded for this month yet.</p>
        <?php endif; ?>
    </div>
</body>
</html>