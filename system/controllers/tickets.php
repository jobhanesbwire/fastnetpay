<?php

_admin();
$admin = Admin::_info();
if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent', 'Sales', 'Report'])) {
    _alert(Lang::T('You do not have permission to access this page'), 'danger', 'dashboard');
}

$ui->assign('_title', 'Support Tickets');
$ui->assign('_system_menu', 'tickets');
$ui->assign('_admin', $admin);

fnp_tickets_ensure_schema();
$action = $routes['1'] ?? 'list';

switch ($action) {
    case 'add':
        fnp_tickets_assign_form($ui);
        $ui->assign('csrf_token', Csrf::generateAndStoreToken());
        $ui->display('admin/tickets/add.tpl');
        break;

    case 'add-post':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::check(_post('csrf_token'))) {
            r2(getUrl('tickets/add'), 'e', Lang::T('Invalid or expired form token'));
        }
        $subject = Text::sanitize(_post('subject'));
        $description = Text::sanitize(_post('description'));
        if ($subject === '' || $description === '') {
            r2(getUrl('tickets/add'), 'e', Lang::T('Subject and description are required'));
        }
        $ticket = ORM::for_table('support_tickets')->create();
        Tenant::stamp($ticket, null, 'support_tickets');
        $ticket->ticket_number = fnp_ticket_number();
        $ticket->subject = $subject;
        $ticket->description = $description;
        $ticket->priority = fnp_ticket_priority(_post('priority'));
        $ticket->status = 'open';
        $ticket->assigned_to = (int) _post('assigned_to');
        $ticket->customer_id = (int) _post('customer_id');
        $ticket->created_by = (int) $admin['id'];
        $ticket->created_at = date('Y-m-d H:i:s');
        $ticket->updated_at = date('Y-m-d H:i:s');
        $ticket->save();
        _log('[' . $admin['username'] . ']: created ticket ' . $ticket->ticket_number, $admin['user_type'], $admin['id']);
        r2(getUrl('tickets/view/' . $ticket->id()), 's', Lang::T('Ticket created successfully'));

    case 'view':
        $ticket = Tenant::scopeIfTenant(ORM::for_table('support_tickets'))->find_one((int) ($routes['2'] ?? 0));
        if (!$ticket) {
            r2(getUrl('tickets/list'), 'e', Lang::T('Ticket not found'));
        }
        fnp_tickets_assign_form($ui);
        $ui->assign('ticket', $ticket);
        $ui->assign('customer', fnp_ticket_customer($ticket['customer_id']));
        $ui->assign('assigned', fnp_ticket_admin($ticket['assigned_to']));
        $ui->assign('creator', fnp_ticket_admin($ticket['created_by']));
        $comments = Tenant::scopeIfTenant(ORM::for_table('support_ticket_comments')->where('ticket_id', (int) $ticket['id'])->order_by_asc('id'))->find_many();
        $ui->assign('comments', $comments);
        $ui->assign('csrf_token', Csrf::generateAndStoreToken());
        $ui->display('admin/tickets/view.tpl');
        break;

    case 'update-post':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::check(_post('csrf_token'))) {
            r2(getUrl('tickets/list'), 'e', Lang::T('Invalid or expired form token'));
        }
        $ticket = Tenant::scopeIfTenant(ORM::for_table('support_tickets'))->find_one((int) _post('id'));
        if (!$ticket) {
            r2(getUrl('tickets/list'), 'e', Lang::T('Ticket not found'));
        }
        $ticket->status = fnp_ticket_status(_post('status'));
        $ticket->priority = fnp_ticket_priority(_post('priority'));
        $ticket->assigned_to = (int) _post('assigned_to');
        $ticket->updated_at = date('Y-m-d H:i:s');
        $ticket->save();
        $comment = trim((string) _post('comment'));
        if ($comment !== '') {
            $row = ORM::for_table('support_ticket_comments')->create();
            Tenant::stamp($row, null, 'support_ticket_comments');
            $row->ticket_id = (int) $ticket['id'];
            $row->admin_id = (int) $admin['id'];
            $row->comment = Text::sanitize($comment);
            $row->created_at = date('Y-m-d H:i:s');
            $row->save();
        }
        _log('[' . $admin['username'] . ']: updated ticket ' . $ticket['ticket_number'], $admin['user_type'], $admin['id']);
        r2(getUrl('tickets/view/' . $ticket['id']), 's', Lang::T('Ticket updated'));

    case 'list':
    default:
        $status = _req('status');
        $priority = _req('priority');
        $assignedTo = _req('assigned_to');
        $search = trim((string) _req('search'));
        $query = ORM::for_table('support_tickets')->order_by_desc('updated_at')->order_by_desc('id');
        $query = Tenant::scopeIfTenant($query);
        if ($status !== '') {
            $query->where('status', fnp_ticket_status($status));
        }
        if ($priority !== '') {
            $query->where('priority', fnp_ticket_priority($priority));
        }
        if ($assignedTo !== '') {
            if ($assignedTo === 'unassigned') {
                $query->where('assigned_to', 0);
            } else {
                $query->where('assigned_to', (int) $assignedTo);
            }
        }
        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where_raw('(ticket_number LIKE ? OR subject LIKE ? OR description LIKE ?)', [$like, $like, $like]);
        }
        $append = http_build_query(['status' => $status, 'priority' => $priority, 'assigned_to' => $assignedTo, 'search' => $search]);
        $tickets = Paginator::findMany($query, [], 30, $append);
        $customerIds = [];
        $adminIds = [];
        foreach ($tickets as $ticket) {
            if ((int) $ticket['customer_id'] > 0) {
                $customerIds[] = (int) $ticket['customer_id'];
            }
            if ((int) $ticket['assigned_to'] > 0) {
                $adminIds[] = (int) $ticket['assigned_to'];
            }
        }
        $ui->assign('tickets', $tickets);
        $ui->assign('customers', fnp_ticket_customers($customerIds));
        $ui->assign('admins_map', fnp_ticket_admins($adminIds));
        $ui->assign('admins', fnp_ticket_assignable_admins());
        $ui->assign('summary', fnp_ticket_summary());
        $ui->assign('status', $status);
        $ui->assign('priority', $priority);
        $ui->assign('assigned_to', $assignedTo);
        $ui->assign('search', $search);
        $ui->display('admin/tickets/list.tpl');
        break;
}

function fnp_tickets_ensure_schema()
{
    ORM::raw_execute("CREATE TABLE IF NOT EXISTS support_tickets (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT UNSIGNED NULL,
        ticket_number VARCHAR(40) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        priority VARCHAR(24) NOT NULL DEFAULT 'medium',
        status VARCHAR(32) NOT NULL DEFAULT 'open',
        assigned_to INT UNSIGNED NOT NULL DEFAULT 0,
        customer_id INT UNSIGNED NOT NULL DEFAULT 0,
        created_by INT UNSIGNED NOT NULL DEFAULT 0,
        created_at DATETIME NULL,
        updated_at DATETIME NULL,
        INDEX idx_tenant_status (tenant_id, status),
        INDEX idx_tenant_priority (tenant_id, priority),
        INDEX idx_assigned (assigned_to),
        INDEX idx_customer (customer_id),
        UNIQUE KEY uniq_ticket_number (ticket_number)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    ORM::raw_execute("CREATE TABLE IF NOT EXISTS support_ticket_comments (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT UNSIGNED NULL,
        ticket_id INT UNSIGNED NOT NULL,
        admin_id INT UNSIGNED NOT NULL DEFAULT 0,
        comment TEXT NOT NULL,
        created_at DATETIME NULL,
        INDEX idx_ticket (ticket_id),
        INDEX idx_tenant_ticket (tenant_id, ticket_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function fnp_ticket_number()
{
    return 'FNP-' . date('ymd') . '-' . strtoupper(substr(Text::randomUpLowCase(md5(uniqid('', true))), 0, 6));
}

function fnp_ticket_status($status)
{
    return in_array($status, ['open', 'in_progress', 'waiting_customer', 'resolved', 'closed'], true) ? $status : 'open';
}

function fnp_ticket_priority($priority)
{
    return in_array($priority, ['low', 'medium', 'high', 'urgent'], true) ? $priority : 'medium';
}

function fnp_ticket_assignable_admins()
{
    $query = ORM::for_table('tbl_users')->where_in('user_type', ['SuperAdmin', 'Admin', 'Agent']);
    if (class_exists('Tenant') && Tenant::isTenantRequest() && Tenant::hasColumn('tbl_users', 'tenant_id')) {
        $query->where('tenant_id', Tenant::currentId());
    }
    return $query->order_by_asc('fullname')->find_array();
}

function fnp_tickets_assign_form($ui)
{
    $ui->assign('admins', fnp_ticket_assignable_admins());
    $ui->assign('customers_list', Tenant::scopeIfTenant(ORM::for_table('tbl_customers')->select('id')->select('username')->select('fullname')->order_by_asc('username'))->limit(500)->find_array());
}

function fnp_ticket_customer($id)
{
    return $id ? Tenant::scopeIfTenant(ORM::for_table('tbl_customers'))->find_one((int) $id) : null;
}

function fnp_ticket_admin($id)
{
    return $id ? ORM::for_table('tbl_users')->find_one((int) $id) : null;
}

function fnp_ticket_customers($ids)
{
    $map = [];
    if (!$ids) {
        return $map;
    }
    $query = ORM::for_table('tbl_customers')->where_id_in(array_unique($ids));
    foreach (Tenant::scopeIfTenant($query)->find_array() as $row) {
        $map[(int) $row['id']] = $row;
    }
    return $map;
}

function fnp_ticket_admins($ids)
{
    $map = [];
    if (!$ids) {
        return $map;
    }
    foreach (ORM::for_table('tbl_users')->where_id_in(array_unique($ids))->find_array() as $row) {
        $map[(int) $row['id']] = $row;
    }
    return $map;
}

function fnp_ticket_summary()
{
    $statuses = ['open', 'in_progress', 'waiting_customer', 'resolved', 'closed'];
    $out = ['total' => (int) Tenant::scopeIfTenant(ORM::for_table('support_tickets'))->count()];
    foreach ($statuses as $status) {
        $out[$status] = (int) Tenant::scopeIfTenant(ORM::for_table('support_tickets')->where('status', $status))->count();
    }
    $out['urgent'] = (int) Tenant::scopeIfTenant(ORM::for_table('support_tickets')->where('priority', 'urgent'))->count();
    return $out;
}
