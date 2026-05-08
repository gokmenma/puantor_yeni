<?php
// Puantor Mobil - Yapılacaklar Listesi
require_once ROOT . "/Model/TodoModel.php";
$todoModel = new Todo();
$todos = $todoModel->getTodosByFirm();

// Gruplandır
$pending_todos = [];
$completed_todos = [];
foreach ($todos as $todo) {
    if (($todo->state ?? 0) == 1) {
        $completed_todos[] = $todo;
    } else {
        $pending_todos[] = $todo;
    }
}
?>

<style>
.section-label {
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #64748b;
    margin-bottom: 1rem;
    margin-top: 1.5rem;
    letter-spacing: 0.5px;
}

.todo-card {
    background: #fff;
    border: 1px solid rgba(0, 0, 0, 0.08);
    border-radius: 16px;
    padding: 1.25rem;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: all 0.2s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.02);
}

.todo-card.completed {
    border-color: #206bc4;
}

.todo-card .form-check-input {
    width: 24px;
    height: 24px;
    border-radius: 8px;
    margin: 0;
    cursor: pointer;
}

.todo-card .form-check-input:checked {
    background-color: #206bc4;
    border-color: #206bc4;
}

.todo-title {
    font-weight: 700;
    font-size: 1rem;
    color: #1d273b;
    margin-bottom: 2px;
}

.todo-card.completed .todo-title {
    text-decoration: line-through;
    color: #94a3b8;
}

.todo-subtitle {
    font-size: 0.8rem;
    color: #64748b;
}

.plus-btn {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    background: #206bc4;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    box-shadow: 0 4px 12px rgba(32, 107, 196, 0.2);
}
</style>

<div class="container px-3">
  <div class="d-flex align-items-center justify-content-between mb-4 mt-2">
    <div>
      <h1 class="mb-0 text-bold" style="font-size: 2rem; letter-spacing: -1px;">Yapılacaklar</h1>
      <p class="text-muted mb-0" style="font-size: 0.9rem;">Görevlerinizi buradan takip edebilirsiniz.</p>
    </div>
    <button class="plus-btn">
      <i class="ti ti-plus" style="font-size: 1.6rem;"></i>
    </button>
  </div>

  <!-- Devam Edenler -->
  <div class="section-label">Devam Edenler (<?php echo count($pending_todos); ?>)</div>
  <?php foreach ($pending_todos as $todo): ?>
    <div class="todo-card">
      <div class="d-flex align-items-center gap-3 w-100">
        <input class="form-check-input" type="checkbox">
        <div class="flex-fill text-center">
          <div class="todo-title"><?php echo htmlspecialchars($todo->title ?? $todo->content ?? 'Görev'); ?></div>
          <?php if (isset($todo->project_name) || isset($todo->created_at)): ?>
            <div class="todo-subtitle"><?php echo htmlspecialchars($todo->project_name ?? date('d M Y', strtotime($todo->created_at))); ?></div>
          <?php endif; ?>
        </div>
        <i class="ti ti-chevron-right text-muted opacity-50"></i>
      </div>
    </div>
  <?php endforeach; ?>

  <!-- Tamamlananlar -->
  <?php if (!empty($completed_todos)): ?>
    <div class="section-label d-flex align-items-center justify-content-between">
      <span>Tamamlananlar (<?php echo count($completed_todos); ?>)</span>
      <i class="ti ti-chevron-down opacity-50" style="font-size: 0.8rem;"></i>
    </div>
    <?php foreach ($completed_todos as $todo): ?>
      <div class="todo-card completed">
        <div class="d-flex align-items-center gap-3 w-100">
          <input class="form-check-input" type="checkbox" checked>
          <div class="flex-fill text-center">
            <div class="todo-title"><?php echo htmlspecialchars($todo->title ?? $todo->content ?? 'Görev'); ?></div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <?php if (empty($todos)): ?>
    <div class="text-center py-5">
      <i class="ti ti-confetti text-muted mb-2" style="font-size: 3rem; opacity: 0.3;"></i>
      <p class="text-muted">Harika! Tüm işler tamamlanmış.</p>
    </div>
  <?php endif; ?>
</div>
