/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

/**
 * The permissions of a role, switched on group by group (admin::role-form).
 *
 *     <div data-permission-matrix>
 *         <span data-permission-total>3</span>
 *         <div data-permission-group>
 *             <input type="checkbox" data-permission-all> <span data-permission-count>3</span>
 *             <input type="checkbox" name="permissions[]" data-permission> …
 *         </div>
 *     </div>
 *
 * The checkbox in the heading of a group ticks or clears the whole group, and shows a dash while
 * only a part of it is on. The counters follow every switch. The checkbox of a group has no name,
 * so only the permissions themselves are posted.
 */
function initPermissionMatrix(root) {
  const total = root.querySelector('[data-permission-total]');
  const groups = Array.from(root.querySelectorAll('[data-permission-group]'));

  function permissionsOf(group) {
    return Array.from(group.querySelectorAll('[data-permission]'));
  }

  function refresh() {
    let granted = 0;

    groups.forEach(function (group) {
      const permissions = permissionsOf(group);
      const on = permissions.filter((permission) => permission.checked).length;
      const all = group.querySelector('[data-permission-all]');

      granted += on;
      group.querySelector('[data-permission-count]').textContent = String(on);
      all.checked = on === permissions.length;
      all.indeterminate = on > 0 && on < permissions.length;
    });

    if (total) {
      total.textContent = String(granted);
    }
  }

  root.addEventListener('change', function (event) {
    if (event.target.matches('[data-permission-all]')) {
      const checked = event.target.checked;
      permissionsOf(event.target.closest('[data-permission-group]')).forEach(function (permission) {
        permission.checked = checked;
      });
    }

    refresh();
  });

  refresh();
}

document.querySelectorAll('[data-permission-matrix]').forEach(initPermissionMatrix);
