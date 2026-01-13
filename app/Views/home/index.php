<h1><?= htmlspecialchars($title ?? 'Home', ENT_QUOTES, 'UTF-8') ?></h1>
<p><?= htmlspecialchars($message ?? '', ENT_QUOTES, 'UTF-8') ?></p>

<style>
.tree {
  --spacing: 1.5rem;
  --radius: 4px;
}

.tree-root {
  --depth: 0;
}

.tree ul {
  margin: 0;
  padding: 0;
  list-style: none;
  padding-left: calc(var(--spacing) + 1rem);
}

.tree li {
  margin: 0.25rem 0;
}

.tree-item-label {
  display: inline-flex;
  align-items: center;
  padding: 0.5rem 0.75rem;
  border-radius: var(--radius);
  cursor: pointer;
  user-select: none;
  transition: all 0.2s ease;
  gap: 0.5rem;
}

.tree-item-label:hover {
  background-color: #f0f0f0;
  color: #007bff;
}

.tree-toggle {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.25rem;
  height: 1.25rem;
  cursor: pointer;
  transition: transform 0.2s ease;
}

.tree-toggle.collapsed {
  transform: rotate(-90deg);
}

.tree-children {
  display: block;
  max-height: 1000px;
  overflow: hidden;
  transition: max-height 0.3s ease;
}

.tree-children.collapsed {
  max-height: 0;
  overflow: hidden;
}

.tree-leaf {
  padding: 0.5rem 0.75rem;
  border-radius: var(--radius);
  color: #007bff;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.2s ease;
}

.tree-leaf:hover {
  background-color: #e7f3ff;
  text-decoration: none;
  color: #0056b3;
}

.tree-icon {
  width: 1.25rem;
  height: 1.25rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
</style>

<div style="margin-top: 30px;">
  <!-- Tree Navigation -->
  <div class="tree">
    <ul>
      <!-- CONSTRUCTION -->
      <li>
        <label class="tree-item-label" onclick="toggleTree(this)">
          <span class="tree-toggle">
            <i class="bi bi-chevron-down"></i>
          </span>
          <i class="bi bi-hammer"></i>
          <strong>CONSTRUCTION</strong>
        </label>
        <ul class="tree-children">
          <!-- TECHNICAL OFFICE -->
          <li>
            <label class="tree-item-label" onclick="toggleTree(this)">
              <span class="tree-toggle">
                <i class="bi bi-chevron-down"></i>
              </span>
              <i class="bi bi-briefcase"></i>
              <strong>TECHNICAL OFFICE</strong>
            </label>
            <ul class="tree-children">
              <li>
                <a href="/projects" class="tree-leaf">
                  <i class="bi bi-diagram-3"></i>
                  Projects
                </a>
              </li>
              <li>
                <a href="/firms" class="tree-leaf">
                  <i class="bi bi-building"></i>
                  Companies
                </a>
              </li>
              <li>
                <a href="/contracts" class="tree-leaf">
                  <i class="bi bi-file-earmark-text"></i>
                  Contracts
                </a>
              </li>
            </ul>
          </li>
          <!-- PURCHASING -->
          <li>
            <label class="tree-item-label" onclick="toggleTree(this)">
              <span class="tree-toggle">
                <i class="bi bi-chevron-down"></i>
              </span>
              <i class="bi bi-cart"></i>
              <strong>PURCHASING</strong>
            </label>
            <ul class="tree-children">
              <li>
                <a href="#" class="tree-leaf">
                  <i class="bi bi-bag"></i>
                  Purchase Orders
                </a>
              </li>
              <li>
                <a href="#" class="tree-leaf">
                  <i class="bi bi-truck"></i>
                  Deliveries
                </a>
              </li>
            </ul>
          </li>
        </ul>
      </li>
      <!-- APPOINTMENT -->
      <li>
        <label class="tree-item-label" onclick="toggleTree(this)">
          <span class="tree-toggle collapsed">
            <i class="bi bi-chevron-down"></i>
          </span>
          <i class="bi bi-calendar-event"></i>
          <strong>APPOINTMENT</strong>
        </label>
        <ul class="tree-children collapsed">
          <li>
            <a href="/appointments" class="tree-leaf">
              <i class="bi bi-calendar2-check"></i>
              Appointments
            </a>
          </li>
        </ul>
      </li>
    </ul>
  </div>
</div>

<hr>

<!-- Users Section -->
<div style="margin-top: 20px;">
  <p><a href="/users" class="btn btn-outline-secondary btn-sm"><i class="bi bi-people me-1"></i>Kullanıcılar sayfasına git</a></p>
</div>

<script>
function toggleTree(label) {
  const toggle = label.querySelector('.tree-toggle');
  const children = label.parentElement.querySelector('.tree-children');
  
  if (toggle && children) {
    toggle.classList.toggle('collapsed');
    children.classList.toggle('collapsed');
  }
}
</script>
