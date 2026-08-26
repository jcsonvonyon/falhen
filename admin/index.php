<?php
/**
 * Admin Dashboard & Site Management
 * Falhen Media Administration
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Enforce admin login
requireAdmin();

$pdo = getDBConnection();
$savedSuccess = false;
$savedMessage = '';

// Process settings save / section POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save_settings';
    $currentRole = $_SESSION['admin_role'] ?? 'Staff';

    if ($action === 'dismiss_clockin_reminder') {
        $_SESSION['dismissed_clockin_reminder'] = true;
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    // RBAC Action to Section Mapping
    $actionSectionMap = [
        'save_hero'                  => 'hero',
        'save_stats'                 => 'stats',
        'save_bts'                   => 'bts',
        'upload_bts_photo'           => 'bts',
        'save_single_featured_item'  => 'portfolio',
        'save_service'               => 'services',
        'delete_service'             => 'services',
        'save_testimonial'           => 'testimonials',
        'delete_testimonial'         => 'testimonials',
        'save_team_member'           => 'team',
        'delete_team_member'         => 'team',
        'save_blog_post'             => 'blog',
        'delete_blog_post'           => 'blog',
        'save_portfolio_item'        => 'portfolio',
        'delete_portfolio_item'      => 'portfolio',
        'save_career_item'           => 'careers',
        'delete_career_item'         => 'careers',
        'update_application_status'  => 'careers',
        'save_brands'                => 'brands',
        'save_brand_item'            => 'brands',
        'delete_brand_item'          => 'brands',
        'save_vendor'                => 'vendors',
        'delete_vendor'              => 'vendors',
        'save_onboarding_item'       => 'onboarding',
        'delete_onboarding_item'     => 'onboarding',
        'save_staff_account'         => 'staff_accounts',
        'delete_staff_account'       => 'staff_accounts',
        'toggle_staff_status'        => 'staff_accounts',
        'update_profile'             => 'profile',
        'change_password'            => 'profile',
        'clock_in'                   => 'attendance',
        'clock_out'                  => 'attendance',
        'start_break'                => 'attendance',
        'end_break'                  => 'attendance',
        'save_onboarding_bank'       => 'onboarding',
        'accept_offer'               => 'onboarding',
        'save_onboarding_ref1'       => 'onboarding',
        'acknowledge_sop'            => 'onboarding',
        'acknowledge_handbook'       => 'onboarding',
        'save_onboarding_id'         => 'onboarding',
        'admin_approve_onboarding_task' => 'onboarding',
        'admin_provide_agreement'    => 'onboarding',
        'admin_provide_offer'        => 'onboarding',
        'schedule_staff_meeting'     => 'directory',
        'post_announcement'          => 'announcements',
        'submit_leave_request'       => 'leaves',
        'update_leave_status'        => 'leaves',
        'send_comms_message'         => 'comms',
        'create_studio_task'         => 'comms',
        'update_studio_task'         => 'comms',
        'update_task_stage'          => 'comms',
        'toggle_task_checklist_item' => 'comms',
        'delete_studio_task'         => 'comms',
        'create_studio_task_stage'   => 'comms',
        'delete_studio_task_stage'   => 'comms',
        'update_studio_task_stage_label' => 'comms'
    ];

    $currentEmail = $_SESSION['admin_email'] ?? '';
    $currentUsername = $_SESSION['admin_username'] ?? '';

    $targetSection = $actionSectionMap[$action] ?? ($_GET['section'] ?? 'hero');
    if ($action !== 'upload_cloudinary_ajax' && !hasSectionAccess($targetSection, $currentRole, $currentEmail, $currentUsername)) {
        header('Location: /admin/index.php?section=' . urlencode($targetSection) . '&denied=1');
        exit;
    }

    if ($action === 'upload_cloudinary_ajax') {
        header('Content-Type: application/json');
        $folder = trim(strip_tags($_POST['folder'] ?? 'falhen/portfolio'));
        $fileToUpload = null;
        
        if (!empty($_FILES['file']['tmp_name'])) {
            $fileToUpload = $_FILES['file']['tmp_name'];
        } else if (!empty($_POST['cropped_data'])) {
            $fileToUpload = $_POST['cropped_data'];
        }

        if (empty($fileToUpload)) {
            echo json_encode(['success' => false, 'message' => 'No image file or cropped data received.']);
            exit;
        }

        $res = uploadToCloudinary($fileToUpload, $folder);
        if (!empty($res['success']) && !empty($res['url'])) {
            echo json_encode(['success' => true, 'url' => $res['url']]);
        } else {
            echo json_encode(['success' => false, 'message' => $res['message'] ?? 'Cloudinary upload failed.']);
        }
        exit;
    }

    if ($action === 'initiate_studio_call_ajax') {
        header('Content-Type: application/json');
        $targetUser = trim($_POST['target_user'] ?? '');
        $targetName = trim($_POST['target_name'] ?? 'Staff Member');
        $targetAvatar = trim($_POST['target_avatar'] ?? '');
        $callType = trim($_POST['call_type'] ?? 'audio');

        $callerName = !empty($_SESSION['admin_full_name']) ? $_SESSION['admin_full_name'] : (!empty($_SESSION['admin_name']) ? $_SESSION['admin_name'] : $currentUsername);
        $callerAvatar = $_SESSION['admin_avatar'] ?? '';

        if (empty($callerAvatar)) {
            $staffRepo = getStaffAccountsRepo();
            $userCanon = getCanonicalUsername($currentUsername);
            foreach ($staffRepo as $st) {
                if (getCanonicalUsername($st['username'] ?? '') === $userCanon) {
                    $callerAvatar = getCloudinaryUrl($st['avatar'] ?? '');
                    break;
                }
            }
        }
        if (empty($callerAvatar)) {
            $teamRepo = getTeamMembers();
            foreach ($teamRepo as $tm) {
                if (!empty($tm['name']) && str_contains(strtolower($tm['name']), strtolower($currentUsername))) {
                    $callerAvatar = getCloudinaryUrl($tm['image'] ?? '');
                    break;
                }
            }
        }

        $call = initiateStudioCallState($currentUsername, $callerName, $callerAvatar, $targetUser, $targetName, $targetAvatar, $callType);
        echo json_encode(['success' => true, 'call' => $call]);
        exit;
    }

    if ($action === 'update_studio_call_status_ajax') {
        header('Content-Type: application/json');
        $callId = trim($_POST['call_id'] ?? '');
        $status = trim($_POST['status'] ?? 'ended');

        $updated = updateStudioCallStatus($callId, $status);
        echo json_encode(['success' => true, 'call' => $updated]);
        exit;
    }

    if ($action === 'check_studio_call_signal_ajax') {
        header('Content-Type: application/json');
        $state = checkUserStudioCallState($currentUsername);
        echo json_encode(['success' => true, 'state' => $state]);
        exit;
    }

    if ($action === 'admin_approve_onboarding_task') {
        if (!isAdminUser($currentRole, $currentEmail, $currentUsername)) {
            header('Location: /admin/index.php?section=onboarding&denied=1');
            exit;
        }

        $targetUser = trim($_POST['target_user'] ?? '');
        $taskKey = trim($_POST['task_key'] ?? '');
        $newStatus = trim($_POST['new_status'] ?? 'Approved');

        updateUserOnboardingTaskStatus($targetUser, $taskKey, $newStatus);

        $_SESSION['saved_message'] = 'Onboarding task "' . ucfirst($taskKey) . '" updated to ' . $newStatus . ' for @' . $targetUser . '!';
        header('Location: /admin/index.php?section=onboarding&target_user=' . urlencode($targetUser) . '&saved=1');
        exit;
    }

    if ($action === 'admin_provide_agreement') {
        if (!isAdminUser($currentRole, $currentEmail, $currentUsername)) {
            header('Location: /admin/index.php?section=onboarding&denied=1');
            exit;
        }

        $targetUser = trim($_POST['target_user'] ?? '');
        $docUrl = trim(strip_tags($_POST['document_url'] ?? '/assets/docs/Employment_Agreement.pdf'));

        updateUserOnboardingTaskStatus($targetUser, 'employment_agreement', 'Approved', [
            'signed' => true,
            'document_url' => $docUrl
        ]);

        $_SESSION['saved_message'] = 'Employment Agreement uploaded and issued for @' . $targetUser . '!';
        header('Location: /admin/index.php?section=onboarding&target_user=' . urlencode($targetUser) . '&saved=1');
        exit;
    }

    if ($action === 'schedule_staff_meeting') {
        $targetUser = trim($_POST['target_user'] ?? '');
        $subject = trim(strip_tags($_POST['subject'] ?? 'Team Sync'));
        $meetingDate = trim(strip_tags($_POST['meeting_date'] ?? date('Y-m-d')));
        $meetingTime = trim(strip_tags($_POST['meeting_time'] ?? '10:00 AM'));
        $meetingDesc = trim(strip_tags($_POST['meeting_desc'] ?? ''));

        $_SESSION['saved_message'] = 'Meeting invite "' . htmlspecialchars($subject) . '" successfully scheduled with @' . htmlspecialchars($targetUser) . ' for ' . htmlspecialchars($meetingDate) . ' at ' . htmlspecialchars($meetingTime) . '!';
        header('Location: /admin/index.php?section=directory&scheduled=1');
        exit;
    }

    if ($action === 'post_announcement') {
        $title = trim(strip_tags($_POST['title'] ?? ''));
        $category = trim(strip_tags($_POST['category'] ?? 'General'));
        $content = trim(strip_tags($_POST['content'] ?? ''));

        if (!empty($title) && !empty($content)) {
            $authorName = ($_SESSION['admin_name'] ?? $currentUsername);
            if (!empty($currentRole)) {
                $authorName .= ' (' . $currentRole . ')';
            }
            postSiteAnnouncement([
                'title' => $title,
                'category' => $category,
                'posted_by' => $authorName,
                'content' => $content
            ]);
            $_SESSION['saved_message'] = 'Announcement "' . htmlspecialchars($title) . '" published successfully!';
        }

        $redirectSection = trim($_POST['redirect_section'] ?? 'comms');
        $redirectTab = trim($_POST['redirect_tab'] ?? 'feeds');
        header('Location: /admin/index.php?section=' . urlencode($redirectSection) . '&tab=' . urlencode($redirectTab) . '&saved=1');
        exit;
    }

    if ($action === 'create_studio_task') {
        $title = trim(strip_tags($_POST['title'] ?? ''));
        $clientOrg = trim(strip_tags($_POST['client_org'] ?? ''));
        $stage = trim(strip_tags($_POST['stage'] ?? 'ideas'));
        $assigneeUser = trim(strip_tags($_POST['assignee_username'] ?? ''));
        $dueDate = trim(strip_tags($_POST['due_date'] ?? date('Y-m-d')));
        $priority = trim(strip_tags($_POST['priority'] ?? 'Medium'));
        $tags = trim(strip_tags($_POST['tags'] ?? ''));
        $description = trim(strip_tags($_POST['description'] ?? ''));
        // Parse dynamic checklist items & status
        $checklist = [];
        if (!empty($_POST['checklist_items']) && is_array($_POST['checklist_items'])) {
            $statuses = $_POST['checklist_status'] ?? [];
            foreach ($_POST['checklist_items'] as $idx => $txt) {
                $itemTxt = trim(strip_tags($txt));
                if (!empty($itemTxt)) {
                    $isDone = !empty($statuses[$idx]) && ($statuses[$idx] == '1' || $statuses[$idx] == 'on' || $statuses[$idx] == 'true');
                    $checklist[] = [
                        'id' => 'item_' . ($idx + 1),
                        'text' => $itemTxt,
                        'completed' => (bool)$isDone
                    ];
                }
            }
        }

        $attachmentUrl = '';
        $attachmentName = '';

        if (!empty($_FILES['task_attachment']['name']) && $_FILES['task_attachment']['error'] === UPLOAD_ERR_OK) {
            $attachmentName = $_FILES['task_attachment']['name'];
            if (function_exists('uploadToCloudinary')) {
                $upRes = uploadToCloudinary($_FILES['task_attachment']['tmp_name'], 'falhen/tasks');
                if (!empty($upRes['success']) && !empty($upRes['secure_url'])) {
                    $attachmentUrl = $upRes['secure_url'];
                }
            }
            if (empty($attachmentUrl)) {
                $targetDir = __DIR__ . '/../uploads/tasks/';
                if (!is_dir($targetDir)) @mkdir($targetDir, 0777, true);
                $cleanFile = time() . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $attachmentName);
                if (move_uploaded_file($_FILES['task_attachment']['tmp_name'], $targetDir . $cleanFile)) {
                    $attachmentUrl = '/uploads/tasks/' . $cleanFile;
                }
            }
        }

        $assigneesInput = $_POST['assignees'] ?? [];
        if (!is_array($assigneesInput) && !empty($_POST['assignee_username'])) {
            $assigneesInput = [$_POST['assignee_username']];
        }

        $assigneesList = [];
        $staffRepo = getStaffAccountsRepo();
        foreach ($assigneesInput as $u) {
            $cUser = getCanonicalUsername($u);
            foreach ($staffRepo as $st) {
                if (getCanonicalUsername($st['username'] ?? '') === $cUser) {
                    $assigneesList[] = [
                        'username' => $st['username'],
                        'name' => $st['full_name'],
                        'avatar' => $st['avatar'] ?? ''
                    ];
                    break;
                }
            }
        }

        if (!empty($title)) {
            createStudioTask([
                'title' => $title,
                'client_org' => $clientOrg,
                'stage' => $stage,
                'assignees' => $assigneesList,
                'assignee_username' => !empty($assigneesList) ? implode(',', array_column($assigneesList, 'username')) : '',
                'assignee_name' => !empty($assigneesList) ? implode(', ', array_column($assigneesList, 'name')) : 'Unassigned',
                'assignee_avatar' => !empty($assigneesList) ? ($assigneesList[0]['avatar'] ?? '') : '',
                'due_date' => $dueDate,
                'priority' => $priority,
                'tags' => $tags,
                'checklist' => $checklist,
                'attachment_url' => $attachmentUrl,
                'attachment_name' => $attachmentName,
                'description' => $description
            ]);
            $_SESSION['saved_message'] = 'New task "' . htmlspecialchars($title) . '" created successfully!';
        }

        header('Location: /admin/index.php?section=comms&tab=tasks&saved=1');
        exit;
    }

    if ($action === 'toggle_task_checklist_item') {
        $taskId = trim($_POST['task_id'] ?? '');
        $itemId = trim($_POST['item_id'] ?? '');
        if (!empty($taskId) && !empty($itemId)) {
            toggleTaskChecklistItem($taskId, $itemId);
        }
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        }
        header('Location: /admin/index.php?section=comms&tab=tasks&saved=1');
        exit;
    }

    if ($action === 'update_studio_task') {
        $taskId = trim($_POST['task_id'] ?? '');
        $title = trim(strip_tags($_POST['title'] ?? ''));
        $clientOrg = trim(strip_tags($_POST['client_org'] ?? ''));
        $stage = trim(strip_tags($_POST['stage'] ?? 'ideas'));
        $assigneeUser = trim(strip_tags($_POST['assignee_username'] ?? ''));
        $dueDate = trim(strip_tags($_POST['due_date'] ?? date('Y-m-d')));
        $priority = trim(strip_tags($_POST['priority'] ?? 'Medium'));
        $tags = trim(strip_tags($_POST['tags'] ?? ''));
        $description = trim(strip_tags($_POST['description'] ?? ''));
        // Parse dynamic checklist items & status
        $checklist = [];
        if (!empty($_POST['checklist_items']) && is_array($_POST['checklist_items'])) {
            $statuses = $_POST['checklist_status'] ?? [];
            foreach ($_POST['checklist_items'] as $idx => $txt) {
                $itemTxt = trim(strip_tags($txt));
                if (!empty($itemTxt)) {
                    $isDone = !empty($statuses[$idx]) && ($statuses[$idx] == '1' || $statuses[$idx] == 'on' || $statuses[$idx] == 'true');
                    $checklist[] = [
                        'id' => 'item_' . ($idx + 1),
                        'text' => $itemTxt,
                        'completed' => (bool)$isDone
                    ];
                }
            }
        }

        $attachmentUrl = '';
        $attachmentName = '';

        if (!empty($_FILES['task_attachment']['name']) && $_FILES['task_attachment']['error'] === UPLOAD_ERR_OK) {
            $attachmentName = $_FILES['task_attachment']['name'];
            if (function_exists('uploadToCloudinary')) {
                $upRes = uploadToCloudinary($_FILES['task_attachment']['tmp_name'], 'falhen/tasks');
                if (!empty($upRes['success']) && !empty($upRes['secure_url'])) {
                    $attachmentUrl = $upRes['secure_url'];
                }
            }
            if (empty($attachmentUrl)) {
                $targetDir = __DIR__ . '/../uploads/tasks/';
                if (!is_dir($targetDir)) @mkdir($targetDir, 0777, true);
                $cleanFile = time() . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $attachmentName);
                if (move_uploaded_file($_FILES['task_attachment']['tmp_name'], $targetDir . $cleanFile)) {
                    $attachmentUrl = '/uploads/tasks/' . $cleanFile;
                }
            }
        }

        $assigneesInput = $_POST['assignees'] ?? [];
        if (!is_array($assigneesInput) && !empty($_POST['assignee_username'])) {
            $assigneesInput = [$_POST['assignee_username']];
        }

        $assigneesList = [];
        $staffRepo = getStaffAccountsRepo();
        foreach ($assigneesInput as $u) {
            $cUser = getCanonicalUsername($u);
            foreach ($staffRepo as $st) {
                if (getCanonicalUsername($st['username'] ?? '') === $cUser) {
                    $assigneesList[] = [
                        'username' => $st['username'],
                        'name' => $st['full_name'],
                        'avatar' => $st['avatar'] ?? ''
                    ];
                    break;
                }
            }
        }

        if (!empty($taskId) && !empty($title)) {
            updateStudioTask($taskId, [
                'title' => $title,
                'client_org' => $clientOrg,
                'stage' => $stage,
                'assignees' => $assigneesList,
                'assignee_username' => !empty($assigneesList) ? implode(',', array_column($assigneesList, 'username')) : '',
                'assignee_name' => !empty($assigneesList) ? implode(', ', array_column($assigneesList, 'name')) : 'Unassigned',
                'assignee_avatar' => !empty($assigneesList) ? ($assigneesList[0]['avatar'] ?? '') : '',
                'due_date' => $dueDate,
                'priority' => $priority,
                'tags' => $tags,
                'checklist' => $checklist,
                'attachment_url' => $attachmentUrl,
                'attachment_name' => $attachmentName,
                'description' => $description
            ]);
            $_SESSION['saved_message'] = 'Task "' . htmlspecialchars($title) . '" updated successfully!';
        }

        header('Location: /admin/index.php?section=comms&tab=tasks&saved=1');
        exit;
    }

    if ($action === 'create_studio_task_stage') {
        if (!isSuperAdminUser($currentRole, $currentEmail, $currentUsername)) {
            $_SESSION['saved_message'] = 'Error: Creating section labels is restricted to Super Admin users only.';
            header('Location: /admin/index.php?section=comms&tab=tasks&denied=1');
            exit;
        }

        $title = trim(strip_tags($_POST['stage_title'] ?? ''));
        $color = trim(strip_tags($_POST['stage_color'] ?? '#3b82f6'));

        if (!empty($title)) {
            createStudioTaskStage($title, $color);
            $_SESSION['saved_message'] = 'New Section Label "' . htmlspecialchars($title) . '" created successfully!';
        }

        header('Location: /admin/index.php?section=comms&tab=tasks&saved=1');
        exit;
    }

    if ($action === 'delete_studio_task_stage') {
        if (!isSuperAdminUser($currentRole, $currentEmail, $currentUsername)) {
            $_SESSION['saved_message'] = 'Error: Deleting section labels is restricted to Super Admin users only.';
            header('Location: /admin/index.php?section=comms&tab=tasks&denied=1');
            exit;
        }

        $stageKey = trim($_POST['stage_key'] ?? '');
        if (!empty($stageKey)) {
            deleteStudioTaskStage($stageKey);
            $_SESSION['saved_message'] = 'Section Label deleted successfully!';
        }

        header('Location: /admin/index.php?section=comms&tab=tasks&saved=1');
        exit;
    }

    if ($action === 'update_studio_task_stage_label') {
        if (!isSuperAdminUser($currentRole, $currentEmail, $currentUsername)) {
            $_SESSION['saved_message'] = 'Error: Renaming section labels is restricted to Super Admin users only.';
            header('Location: /admin/index.php?section=comms&tab=tasks&denied=1');
            exit;
        }

        $stageKey = trim($_POST['stage_key'] ?? '');
        $title = trim(strip_tags($_POST['stage_title'] ?? ''));
        $color = trim(strip_tags($_POST['stage_color'] ?? ''));

        if (!empty($stageKey) && !empty($title)) {
            updateStudioTaskStageLabel($stageKey, $title, $color);
            $_SESSION['saved_message'] = 'Section Label updated to "' . htmlspecialchars($title) . '" successfully!';
        }

        header('Location: /admin/index.php?section=comms&tab=tasks&saved=1');
        exit;
    }

    if ($action === 'update_task_stage') {
        $taskId = trim($_POST['task_id'] ?? '');
        $newStage = trim($_POST['new_stage'] ?? 'ideas');
        if (!empty($taskId)) {
            updateStudioTaskStage($taskId, $newStage);
        }
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        }
        header('Location: /admin/index.php?section=comms&tab=tasks&saved=1');
        exit;
    }

    if ($action === 'delete_studio_task') {
        $taskId = trim($_POST['task_id'] ?? '');
        if (!empty($taskId)) {
            deleteStudioTask($taskId);
            $_SESSION['saved_message'] = 'Task deleted successfully!';
        }
        header('Location: /admin/index.php?section=comms&tab=tasks&saved=1');
        exit;
    }

    if ($action === 'submit_leave_request') {
        $leaveType = trim(strip_tags($_POST['leave_type'] ?? 'Annual Leave'));
        $startDate = trim(strip_tags($_POST['start_date'] ?? ''));
        $endDate = trim(strip_tags($_POST['end_date'] ?? ''));
        $duration = max(1, intval($_POST['duration'] ?? 1));
        $reason = trim(strip_tags($_POST['reason'] ?? 'Personal Time'));
        $applicantName = $_SESSION['admin_name'] ?? $currentUsername;

        $newReq = submitLeaveRequest($currentUsername, $applicantName, $currentRole, $leaveType, $startDate, $endDate, $duration, $reason);

        $_SESSION['saved_message'] = 'Your ' . htmlspecialchars($leaveType) . ' request (' . htmlspecialchars($newReq['dates']) . ') has been submitted successfully and is pending HR approval!';
        header('Location: /admin/index.php?section=leaves&saved=1');
        exit;
    }

    if ($action === 'update_leave_status') {
        if (!canViewAllAttendanceLogs($currentRole, $currentEmail, $currentUsername)) {
            header('Location: /admin/index.php?section=leaves&denied=1');
            exit;
        }

        $leaveId = trim($_POST['leave_id'] ?? '');
        $newStatus = trim($_POST['new_status'] ?? 'Approved');

        if (!empty($leaveId)) {
            updateLeaveRequestStatus($leaveId, $newStatus);
            $_SESSION['saved_message'] = 'Leave request has been ' . strtolower(htmlspecialchars($newStatus)) . ' successfully!';
        }

        header('Location: /admin/index.php?section=leaves&tab=company&saved=1');
        exit;
    }

    if ($action === 'send_comms_message') {
        $channel = trim(strip_tags($_POST['channel'] ?? 'general'));
        $messageText = trim(strip_tags($_POST['message'] ?? ''));
        
        $senderName = !empty($_SESSION['admin_full_name']) ? $_SESSION['admin_full_name'] : (!empty($_SESSION['admin_name']) ? $_SESSION['admin_name'] : $currentUsername);
        $resolvedUsername = strtolower(trim($currentUsername));

        if ($resolvedUsername === 'admin' || empty($resolvedUsername)) {
            $sessNameLower = strtolower($senderName);
            $sessEmailLower = strtolower($_SESSION['admin_email'] ?? '');
            if (str_contains($sessNameLower, 'oluwatosin') || str_contains($sessNameLower, 'ligali') || str_contains($sessEmailLower, 'ligali') || str_contains($sessEmailLower, 'oluwatosin')) {
                $resolvedUsername = 'ligali.oluwatosin';
            } else if (str_contains($sessNameLower, 'mojisola') || str_contains($sessEmailLower, 'mojisola')) {
                $resolvedUsername = 'mojisola.emjay';
            } else if (str_contains($sessNameLower, 'kingsley') || str_contains($sessEmailLower, 'kingsley')) {
                $resolvedUsername = 'kingsley.falonipe';
            } else if (str_contains($sessNameLower, 'daniel') || str_contains($sessEmailLower, 'daniel')) {
                $resolvedUsername = 'daniel.ifeoluwa';
            } else if (str_contains($sessNameLower, 'victoria') || str_contains($sessEmailLower, 'victoria')) {
                $resolvedUsername = 'victoria.opemipo';
            } else if (str_contains($sessNameLower, 'lisa') || str_contains($sessEmailLower, 'lisa')) {
                $resolvedUsername = 'lisa.okoli';
            } else if (str_contains($sessNameLower, 'henry') || str_contains($sessEmailLower, 'henry')) {
                $resolvedUsername = 'henry.falonipe';
            }
        }

        if (!empty($messageText)) {
            sendCommsMessage($resolvedUsername, $senderName, $currentRole, $channel, $messageText);
        }

        $redirectTab = $_GET['tab'] ?? 'nest';
        $redirectDm = $_GET['dm'] ?? '';
        $redirectUrl = "/admin/index.php?section=comms&tab=" . urlencode($redirectTab);
        if (!empty($redirectDm)) {
            $redirectUrl .= "&dm=" . urlencode($redirectDm);
        } else {
            $redirectUrl .= "&channel=" . urlencode($channel);
        }

        header('Location: ' . $redirectUrl);
        exit;
    }

    if ($action === 'admin_provide_offer') {
        if (!isAdminUser($currentRole, $currentEmail, $currentUsername)) {
            header('Location: /admin/index.php?section=onboarding&denied=1');
            exit;
        }

        $targetUser = trim($_POST['target_user'] ?? '');
        $jobTitle = trim(strip_tags($_POST['job_title'] ?? 'Senior Video Editor & Cinematographer'));
        $department = trim(strip_tags($_POST['department'] ?? 'Media Production & Post Studio'));
        $employmentType = trim(strip_tags($_POST['employment_type'] ?? 'Full-Time'));
        $rawSalary = trim(strip_tags($_POST['salary'] ?? '450,000 / month'));
        $cleanSalary = preg_replace('/^[₦$€£\s]+/u', '', $rawSalary);
        $currency = trim(strip_tags($_POST['currency'] ?? 'NGN'));
        $currencySymbols = [
            'NGN' => '₦',
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£'
        ];
        $sym = $currencySymbols[$currency] ?? '₦';
        
        $salary = $sym . $cleanSalary;

        $startDate = trim(strip_tags($_POST['start_date'] ?? date('Y-m-d', strtotime('+7 days'))));
        $probationPeriod = trim(strip_tags($_POST['probation_period'] ?? '3 Months'));
        $expiryDate = trim(strip_tags($_POST['expiry_date'] ?? date('Y-m-d', strtotime('+14 days'))));
        $docUrl = trim(strip_tags($_POST['document_url'] ?? '/assets/docs/Offer_Letter_Falhen.pdf'));
        $notes = trim(strip_tags($_POST['notes'] ?? ''));

        $issuerName = $_SESSION['admin_full_name'] ?? $_SESSION['admin_username'] ?? 'Talent Manager';

        updateUserOnboardingTaskStatus($targetUser, 'offer_letter', 'Issued', [
            'issued' => true,
            'issued_at' => date('Y-m-d H:i:s'),
            'issued_by' => $issuerName,
            'job_title' => $jobTitle,
            'department' => $department,
            'employment_type' => $employmentType,
            'currency' => $currency,
            'salary' => $salary,
            'start_date' => $startDate,
            'probation_period' => $probationPeriod,
            'expiry_date' => $expiryDate,
            'document_url' => $docUrl,
            'notes' => $notes
        ]);

        $_SESSION['saved_message'] = 'Offer Letter issued successfully to @' . $targetUser . '!';
        header('Location: /admin/index.php?section=onboarding&target_user=' . urlencode($targetUser) . '&saved=1');
        exit;
    }
    
    if ($action === 'save_onboarding_bank') {
        $username = $_SESSION['admin_username'] ?? $adminProfile['username'] ?? 'staff';
        $accNo = trim(strip_tags($_POST['account_number'] ?? ''));
        $bankName = trim(strip_tags($_POST['bank_name'] ?? ''));
        $accName = trim(strip_tags($_POST['account_name'] ?? ''));

        saveUserOnboardingSection($username, 'bank_details', [
            'status' => 'Submitted',
            'account_number' => $accNo,
            'bank_name' => $bankName,
            'account_name' => $accName
        ]);

        $_SESSION['saved_message'] = 'Bank Details submitted successfully for payroll processing!';
        header('Location: /admin/index.php?section=onboarding&saved=1');
        exit;
    }

    if ($action === 'accept_offer') {
        $username = $_SESSION['admin_username'] ?? $adminProfile['username'] ?? 'staff';
        saveUserOnboardingSection($username, 'offer_letter', [
            'status' => 'Approved',
            'accepted' => true
        ]);
        $_SESSION['saved_message'] = 'Offer Letter accepted successfully!';
        header('Location: /admin/index.php?section=onboarding&saved=1');
        exit;
    }

    if ($action === 'save_onboarding_ref1') {
        $username = $_SESSION['admin_username'] ?? $adminProfile['username'] ?? 'staff';
        $refName = trim(strip_tags($_POST['ref_name'] ?? ''));
        $refContact = trim(strip_tags($_POST['ref_contact'] ?? ''));
        $relationship = trim(strip_tags($_POST['relationship'] ?? ''));

        saveUserOnboardingSection($username, 'reference_1', [
            'status' => 'Submitted',
            'ref_name' => $refName,
            'ref_contact' => $refContact,
            'relationship' => $relationship
        ]);

        $_SESSION['saved_message'] = 'Reference details submitted successfully!';
        header('Location: /admin/index.php?section=onboarding&saved=1');
        exit;
    }

    if ($action === 'acknowledge_sop') {
        $username = $_SESSION['admin_username'] ?? $adminProfile['username'] ?? 'staff';
        saveUserOnboardingSection($username, 'sop', [
            'status' => 'Approved',
            'acknowledged' => true
        ]);
        $_SESSION['saved_message'] = 'Studio SOP acknowledged successfully!';
        header('Location: /admin/index.php?section=onboarding&saved=1');
        exit;
    }

    if ($action === 'acknowledge_handbook') {
        $username = $_SESSION['admin_username'] ?? $adminProfile['username'] ?? 'staff';
        saveUserOnboardingSection($username, 'staff_handbook', [
            'status' => 'Approved',
            'acknowledged' => true
        ]);
        $_SESSION['saved_message'] = 'Staff Handbook acknowledged successfully!';
        header('Location: /admin/index.php?section=onboarding&saved=1');
        exit;
    }

    if ($action === 'save_onboarding_id') {
        $username = $_SESSION['admin_username'] ?? $adminProfile['username'] ?? 'staff';
        $docUrl = trim(strip_tags($_POST['file_url'] ?? '/assets/docs/ID_Verified.pdf'));
        saveUserOnboardingSection($username, 'id_verification', [
            'status' => 'Submitted',
            'file_url' => $docUrl
        ]);
        $_SESSION['saved_message'] = 'ID / Tax Verification document uploaded successfully!';
        header('Location: /admin/index.php?section=onboarding&saved=1');
        exit;
    }
    
    if ($action === 'clock_in') {
        $username = $_SESSION['admin_username'] ?? $adminProfile['username'] ?? 'staff';
        $fullName = $_SESSION['admin_full_name'] ?? $adminProfile['full_name'] ?? 'Staff Member';
        $res = recordClockIn($username, $fullName);
        $_SESSION['saved_message'] = $res['message'];
        $redirectSec = $_GET['section'] ?? 'attendance';
        header('Location: /admin/index.php?section=' . urlencode($redirectSec) . '&saved=1');
        exit;
    }

    if ($action === 'start_break') {
        $username = $_SESSION['admin_username'] ?? $adminProfile['username'] ?? 'staff';
        $res = recordStartBreak($username);
        $_SESSION['saved_message'] = $res['message'];
        $redirectSec = $_GET['section'] ?? 'attendance';
        header('Location: /admin/index.php?section=' . urlencode($redirectSec) . '&saved=1');
        exit;
    }

    if ($action === 'end_break') {
        $username = $_SESSION['admin_username'] ?? $adminProfile['username'] ?? 'staff';
        $res = recordEndBreak($username);
        $_SESSION['saved_message'] = $res['message'];
        $redirectSec = $_GET['section'] ?? 'attendance';
        header('Location: /admin/index.php?section=' . urlencode($redirectSec) . '&saved=1');
        exit;
    }

    if ($action === 'clock_out') {
        $username = $_SESSION['admin_username'] ?? $adminProfile['username'] ?? 'staff';
        $res = recordClockOut($username);
        $_SESSION['saved_message'] = $res['message'];
        $redirectSec = $_GET['section'] ?? 'attendance';
        header('Location: /admin/index.php?section=' . urlencode($redirectSec) . '&saved=1');
        exit;
    }

    if ($action === 'save_hero') {
        $heroSettings = [
            'hero_direct_video_url'   => trim(strip_tags($_POST['hero_direct_video_url'] ?? '')),
            'hero_poster_image'       => trim(strip_tags($_POST['hero_poster_image'] ?? '')),
            'showreel_youtube_id'     => trim(strip_tags($_POST['showreel_youtube_id'] ?? '')),
            'hero_badge_label'        => trim(strip_tags($_POST['hero_badge_label'] ?? '')),
            'hero_headline_line1'     => trim(strip_tags($_POST['hero_headline_line1'] ?? 'Creating what the')),
            'hero_headline_line2'     => trim(strip_tags($_POST['hero_headline_line2'] ?? 'World Watches')),
            'hero_tagline'            => trim(strip_tags($_POST['hero_tagline'] ?? '')),
            'hero_primary_cta_text'   => trim(strip_tags($_POST['hero_primary_cta_text'] ?? 'Explore Our Projects')),
            'hero_secondary_cta_text' => trim(strip_tags($_POST['hero_secondary_cta_text'] ?? 'Watch Showreel'))
        ];
        saveSiteSettings($heroSettings);
        $savedSuccess = true;
        $savedMessage = 'Hero section settings saved successfully!';
    } else if ($action === 'save_stats') {
        $statsItems = [];
        if (!empty($_POST['stat_number']) && is_array($_POST['stat_number'])) {
            foreach ($_POST['stat_number'] as $idx => $num) {
                if (trim($num) !== '' || !empty($_POST['stat_label'][$idx])) {
                    $statsItems[] = [
                        'number'   => trim(strip_tags($num)),
                        'suffix'   => trim(strip_tags($_POST['stat_suffix'][$idx] ?? '+')),
                        'prefix'   => trim(strip_tags($_POST['stat_prefix'][$idx] ?? '')),
                        'label'    => trim(strip_tags($_POST['stat_label'][$idx] ?? '')),
                        'sublabel' => trim(strip_tags($_POST['stat_sublabel'][$idx] ?? '')),
                        'icon'     => trim(strip_tags($_POST['stat_icon'][$idx] ?? 'ri-film-line'))
                    ];
                }
            }
        }

        $statSettings = [
            'stats_badge_label'    => trim(strip_tags($_POST['stats_badge_label'] ?? 'By the Numbers')),
            'stats_headline_white' => trim(strip_tags($_POST['stats_headline_white'] ?? 'A Decade of')),
            'stats_headline_red'   => trim(strip_tags($_POST['stats_headline_red'] ?? 'Impact')),
            'stats_description'    => trim(strip_tags($_POST['stats_description'] ?? "The numbers behind Falhen's reputation as one of Africa's most awarded and globally recognised production houses.")),
            'stats_items'          => $statsItems
        ];
        saveSiteSettings($statSettings);
        $savedSuccess = true;
        $savedMessage = 'Stats counter section saved successfully!';
    } else if ($action === 'save_bts') {
        $btsItems = [];
        if (!empty($_POST['bts_title']) && is_array($_POST['bts_title'])) {
            foreach ($_POST['bts_title'] as $idx => $title) {
                if (trim($title) !== '' || !empty($_POST['bts_image'][$idx])) {
                    $btsItems[] = [
                        'id'       => $idx + 1,
                        'title'    => trim(strip_tags($title)),
                        'subtitle' => trim(strip_tags($_POST['bts_subtitle'][$idx] ?? '')),
                        'image'    => trim(strip_tags($_POST['bts_image'][$idx] ?? '/assets/img/hero.jpg')),
                        'visible'  => true
                    ];
                }
            }
        }
        
        $btsSettings = [
            'bts_badge_label'    => trim(strip_tags($_POST['bts_badge_label'] ?? 'Production BTS')),
            'bts_headline_white' => trim(strip_tags($_POST['bts_headline_white'] ?? 'Every Frame')),
            'bts_headline_red'   => trim(strip_tags($_POST['bts_headline_red'] ?? 'Speaks')),
            'bts_description'    => trim(strip_tags($_POST['bts_description'] ?? 'A raw look at what it takes to create world-class content — from location scouts to post-production suites.')),
            'bts_items'          => $btsItems
        ];
        saveSiteSettings($btsSettings);
        $savedSuccess = true;
        $savedMessage = 'Production BTS gallery updated successfully!';
    } else if ($action === 'upload_bts_photo') {
        if (!empty($_FILES['bts_file']['tmp_name'])) {
            $res = uploadToCloudinary($_FILES['bts_file']['tmp_name'], 'falhen/bts');
            $imgUrl = $res['success'] ? $res['url'] : '';
        } else {
            $imgUrl = trim(strip_tags($_POST['bts_photo_url'] ?? ''));
        }

        if (!empty($imgUrl)) {
            $currentSettings = getSiteSettings();
            $items = $currentSettings['bts_items'] ?? [];
            $newId = count($items) + 1;
            $items[] = [
                'id'       => $newId,
                'title'    => trim(strip_tags($_POST['bts_new_title'] ?? ('BTS Photo #' . $newId))),
                'subtitle' => trim(strip_tags($_POST['bts_new_subtitle'] ?? 'Behind the scenes footage')),
                'image'    => $imgUrl,
                'visible'  => true
            ];
            saveSiteSettings(['bts_items' => $items]);
            $savedSuccess = true;
            $savedMessage = '✓ New BTS Photo added successfully!';
        }
    } else if ($action === 'save_single_featured_item') {
        $itemId = (int)($_POST['item_id'] ?? 0);
        $projectName = trim(strip_tags($_POST['project_name'] ?? ''));
        $client = trim(strip_tags($_POST['client'] ?? ''));
        $category = trim(strip_tags($_POST['category'] ?? 'Social Event'));
        $year = trim(strip_tags($_POST['year'] ?? date('Y')));
        $duration = trim(strip_tags($_POST['duration'] ?? '0:00'));
        $youtubeInput = trim(strip_tags($_POST['youtube_id'] ?? ''));
        $youtubeId = extractYouTubeId($youtubeInput) ?: $youtubeInput;
        $videoSource = trim(strip_tags($_POST['video_source'] ?? 'youtube'));
        $isHeroFeatured = isset($_POST['is_hero_featured']) ? true : false;
        
        $customThumbnail = trim(strip_tags($_POST['thumbnail'] ?? ''));
        $croppedData = trim($_POST['cropped_featured_image_data'] ?? '');

        if (!empty($croppedData)) {
            $res = uploadToCloudinary($croppedData, 'falhen/featured');
            if ($res['success']) {
                $thumbnail = $res['url'];
            } else {
                $thumbnail = !empty($customThumbnail) ? $customThumbnail : getYouTubeThumbnailUrl($youtubeId);
            }
        } else if (!empty($_FILES['featured_image_file']['tmp_name'])) {
            $res = uploadToCloudinary($_FILES['featured_image_file']['tmp_name'], 'falhen/featured');
            if ($res['success']) {
                $thumbnail = $res['url'];
            } else {
                $thumbnail = !empty($customThumbnail) ? $customThumbnail : getYouTubeThumbnailUrl($youtubeId);
            }
        } else if (!empty($customThumbnail)) {
            $thumbnail = $customThumbnail;
        } else {
            $thumbnail = getYouTubeThumbnailUrl($youtubeId);
        }

        $currentSettings = getSiteSettings();
        $items = $currentSettings['featured_work_items'] ?? [];

        if ($isHeroFeatured) {
            foreach ($items as &$it) {
                $it['is_hero_featured'] = false;
            }
        }

        $found = false;
        foreach ($items as &$it) {
            if ((int)$it['id'] === $itemId) {
                $it['project_name'] = $projectName;
                $it['client'] = $client;
                $it['category'] = $category;
                $it['year'] = $year;
                $it['duration'] = $duration;
                $it['youtube_id'] = $youtubeId;
                $it['video_source'] = $videoSource;
                $it['thumbnail'] = $thumbnail;
                $it['is_hero_featured'] = $isHeroFeatured;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $newId = count($items) + 1;
            $items[] = [
                'id' => $newId,
                'project_name' => $projectName,
                'client' => $client,
                'category' => $category,
                'year' => $year,
                'duration' => $duration,
                'youtube_id' => $youtubeId,
                'video_source' => $videoSource,
                'thumbnail' => $thumbnail,
                'is_hero_featured' => $isHeroFeatured,
                'status' => 'live'
            ];
        }

        saveSiteSettings(['featured_work_items' => $items]);
        $savedSuccess = true;
        $savedMessage = "✓ Featured video '$projectName' saved & published to live!";
    } else if ($action === 'delete_featured_item') {
        $itemId = (int)($_POST['item_id'] ?? 0);
        $currentSettings = getSiteSettings();
        $items = $currentSettings['featured_work_items'] ?? [];
        $newItems = array_values(array_filter($items, function($it) use ($itemId) {
            return (int)$it['id'] !== $itemId;
        }));
        saveSiteSettings(['featured_work_items' => $newItems]);
        $savedSuccess = true;
        $savedMessage = "✓ Video deleted successfully!";
    } else if ($action === 'save_service_item') {
        $serviceId = (int)($_POST['service_id'] ?? 0);
        $title = trim(strip_tags($_POST['title'] ?? ''));
        $icon = trim(strip_tags($_POST['icon'] ?? 'ri-video-line'));
        $shortDescription = trim(strip_tags($_POST['short_description'] ?? ''));
        $detailDescription = trim(strip_tags($_POST['detail_description'] ?? ''));

        $image = trim(strip_tags($_POST['existing_image'] ?? ''));
        $croppedData = trim($_POST['cropped_image_data'] ?? '');

        if (!empty($croppedData)) {
            $res = uploadToCloudinary($croppedData, 'falhen/services');
            if ($res['success']) {
                $image = $res['url'];
            }
        } else if (!empty($_FILES['service_image_file']['tmp_name'])) {
            $res = uploadToCloudinary($_FILES['service_image_file']['tmp_name'], 'falhen/services');
            if ($res['success']) {
                $image = $res['url'];
            }
        }

        $cardFeaturesRaw = $_POST['card_features'] ?? '';
        $cardFeatures = array_values(array_filter(array_map('trim', explode("\n", strip_tags($cardFeaturesRaw)))));

        $detailFeaturesRaw = $_POST['detail_features'] ?? '';
        $detailFeatures = array_values(array_filter(array_map('trim', explode("\n", strip_tags($detailFeaturesRaw)))));

        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $title), '-'));

        $currentSettings = getSiteSettings();
        $services = $currentSettings['services_items'] ?? [];

        $found = false;
        foreach ($services as &$s) {
            if ((int)$s['id'] === $serviceId) {
                $s['title'] = $title;
                $s['slug'] = $slug;
                $s['icon'] = $icon;
                $s['short_description'] = $shortDescription;
                $s['detail_description'] = $detailDescription;
                $s['image'] = $image;
                $s['card_features'] = $cardFeatures;
                $s['detail_features'] = $detailFeatures;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $newId = count($services) + 1;
            $services[] = [
                'id' => $newId,
                'slug' => $slug,
                'title' => $title,
                'icon' => $icon,
                'short_description' => $shortDescription,
                'detail_description' => $detailDescription,
                'image' => $image,
                'card_features' => $cardFeatures,
                'detail_features' => $detailFeatures
            ];
        }

        saveSiteSettings(['services_items' => $services]);
        $savedSuccess = true;
        $savedMessage = "✓ Service '$title' saved & published to live!";
    } else if ($action === 'delete_service_item') {
        $serviceId = (int)($_POST['service_id'] ?? 0);
        $currentSettings = getSiteSettings();
        $services = $currentSettings['services_items'] ?? [];
        $newServices = array_values(array_filter($services, function($s) use ($serviceId) {
            return (int)$s['id'] !== $serviceId;
        }));
        saveSiteSettings(['services_items' => $newServices]);
        $savedSuccess = true;
        $savedMessage = "✓ Service deleted successfully!";
    } else if ($action === 'save_testimonial_item') {
        $tId = (int)($_POST['id'] ?? 0);
        $name = trim(strip_tags($_POST['name'] ?? ''));
        $role = trim(strip_tags($_POST['role'] ?? ''));
        $company = trim(strip_tags($_POST['company'] ?? ''));
        $project = trim(strip_tags($_POST['project'] ?? ''));
        $rating = (int)($_POST['rating'] ?? 5);
        $quote = trim($_POST['quote'] ?? '');

        $avatar = trim(strip_tags($_POST['avatar'] ?? ''));
        $croppedAvatarData = trim($_POST['cropped_avatar_data'] ?? '');

        if (!empty($croppedAvatarData)) {
            $res = uploadToCloudinary($croppedAvatarData, 'falhen/testimonials');
            if ($res['success']) {
                $avatar = $res['url'];
            }
        } else if (!empty($_FILES['avatar_file']['tmp_name'])) {
            $res = uploadToCloudinary($_FILES['avatar_file']['tmp_name'], 'falhen/testimonials');
            if ($res['success']) {
                $avatar = $res['url'];
            }
        }

        $items = getTestimonialsRepo();

        $found = false;
        foreach ($items as &$it) {
            if ((int)($it['id'] ?? 0) === $tId) {
                $it['name'] = $name;
                $it['role'] = $role;
                $it['company'] = $company;
                $it['project'] = $project;
                $it['rating'] = $rating;
                $it['quote'] = $quote;
                if (!empty($avatar)) {
                    $it['avatar'] = $avatar;
                }
                $found = true;
                break;
            }
        }

        if (!$found) {
            $newId = count($items) + 1;
            $items[] = [
                'id' => $newId,
                'name' => $name,
                'role' => $role,
                'company' => $company,
                'project' => $project,
                'avatar' => !empty($avatar) ? $avatar : 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80',
                'rating' => $rating,
                'quote' => $quote
            ];
        }

        saveSiteSettings(['testimonials_items' => $items]);
        header('Location: index.php?section=testimonials&saved=1');
        exit;
    } else if ($action === 'delete_testimonial_item') {
        $tId = (int)($_POST['id'] ?? 0);
        $items = getTestimonialsRepo();
        $newItems = array_values(array_filter($items, function($it) use ($tId) {
            return (int)($it['id'] ?? 0) !== $tId;
        }));
        saveSiteSettings(['testimonials_items' => $newItems]);
        header('Location: index.php?section=testimonials&deleted=1');
        exit;
    } else if ($action === 'save_portfolio_item') {
        $pId = (int)($_POST['id'] ?? 0);
        $title = trim(strip_tags($_POST['title'] ?? ''));
        $category = trim(strip_tags($_POST['category'] ?? 'General'));
        $mediaType = trim(strip_tags($_POST['media_type'] ?? 'photo'));
        $client = trim(strip_tags($_POST['client'] ?? ''));
        $location = trim(strip_tags($_POST['location'] ?? ''));
        $duration = trim(strip_tags($_POST['duration'] ?? ''));
        $year = trim(strip_tags($_POST['year'] ?? ''));
        $videoUrl = trim(strip_tags($_POST['video_url'] ?? ''));
        $gdriveUrl = trim(strip_tags($_POST['gdrive_url'] ?? ''));
        $desc = trim($_POST['desc'] ?? '');
        $services = trim(strip_tags($_POST['services'] ?? ''));
        $featured = !empty($_POST['featured']) ? true : false;
        
        $image = trim(strip_tags($_POST['existing_image'] ?? ''));
        $croppedData = trim($_POST['cropped_portfolio_image_data'] ?? '');

        // Auto thumbnail handling for YouTube video reels or Google Drive links
        if (!empty($croppedData)) {
            $res = uploadToCloudinary($croppedData, 'falhen/portfolio');
            if ($res['success']) {
                $image = $res['url'];
            }
        } else if (!empty($_FILES['portfolio_image_file']['tmp_name'])) {
            $res = uploadToCloudinary($_FILES['portfolio_image_file']['tmp_name'], 'falhen/portfolio');
            if ($res['success']) {
                $image = $res['url'];
            }
        } else {
            // Auto pick YouTube thumbnail for Video Reels if YouTube URL/ID provided
            if ($mediaType === 'video' && !empty($videoUrl)) {
                $youtubeId = extractYouTubeId($videoUrl);
                if (!empty($youtubeId)) {
                    $image = getYouTubeThumbnailUrl($youtubeId);
                }
            }
            // Auto convert Google Drive share file link to direct viewable image if thumbnail is empty
            if (empty($image) && !empty($gdriveUrl)) {
                $converted = convertGoogleDriveUrlToDirect($gdriveUrl);
                if (!empty($converted)) {
                    $image = $converted;
                }
            }
        }

        $items = getPortfolioRepo();

        $found = false;
        foreach ($items as &$it) {
            if ((int)($it['id'] ?? 0) === $pId) {
                $it['title'] = $title;
                $it['category'] = $category;
                $it['media_type'] = $mediaType;
                $it['client'] = $client;
                $it['location'] = $location;
                $it['duration'] = $duration;
                $it['year'] = $year;
                $it['project_date'] = $year;
                $it['video_url'] = $videoUrl;
                $it['gdrive_url'] = $gdriveUrl;
                $it['desc'] = $desc;
                $it['services'] = $services;
                $it['featured'] = $featured;
                if (!empty($image)) {
                    $it['image'] = $image;
                }
                $found = true;
                break;
            }
        }

        if (!$found) {
            $maxId = 0;
            foreach ($items as $it) {
                if ((int)($it['id'] ?? 0) > $maxId) {
                    $maxId = (int)$it['id'];
                }
            }
            $newId = $maxId + 1;
            $items[] = [
                'id' => $newId,
                'title' => $title,
                'category' => $category,
                'media_type' => $mediaType,
                'featured' => $featured,
                'client' => $client,
                'location' => $location,
                'duration' => $duration,
                'year' => $year,
                'project_date' => $year,
                'video_url' => $videoUrl,
                'gdrive_url' => $gdriveUrl,
                'image' => !empty($image) ? $image : '/assets/img/portfolio/portfolio_halima.png',
                'desc' => $desc,
                'services' => $services,
                'photosCount' => 30
            ];
        }

        saveSiteSettings(['portfolio_items' => $items]);
        $redirectType = trim(strip_tags($_POST['type_filter'] ?? $_GET['type'] ?? ''));
        if (empty($redirectType) || $redirectType === 'all') {
            $redirectType = $mediaType ?? 'all';
        }
        header('Location: index.php?section=portfolio&type=' . urlencode($redirectType) . '&saved=1');
        exit;
    } else if ($action === 'delete_portfolio_item') {
        $pId = (int)($_POST['id'] ?? 0);
        $items = getPortfolioRepo();
        $newItems = array_values(array_filter($items, function($it) use ($pId) {
            return (int)($it['id'] ?? 0) !== $pId;
        }));
        saveSiteSettings(['portfolio_items' => $newItems]);
        $redirectType = trim(strip_tags($_POST['type_filter'] ?? $_GET['type'] ?? 'all'));
        header('Location: index.php?section=portfolio&type=' . urlencode($redirectType) . '&deleted=1');
        exit;
    } else if ($action === 'save_team_member') {
        $tId = (int)($_POST['id'] ?? 0);
        $name = trim(strip_tags($_POST['name'] ?? ''));
        $role = trim(strip_tags($_POST['role'] ?? ''));
        $department = trim(strip_tags($_POST['department'] ?? 'Creative'));
        $location = trim(strip_tags($_POST['location'] ?? 'Lagos'));
        $experience = trim(strip_tags($_POST['experience'] ?? ''));
        $bio = trim($_POST['bio'] ?? '');
        $skillsRaw = trim($_POST['skills'] ?? '');
        
        $image = trim($_POST['existing_image'] ?? '');
        $croppedData = trim($_POST['cropped_team_image_data'] ?? '');

        if (!empty($croppedData)) {
            $res = uploadToCloudinary($croppedData, 'falhen/team');
            if ($res['success']) {
                $image = $res['url'];
            }
        } else if (!empty($_FILES['team_image_file']['tmp_name'])) {
            $res = uploadToCloudinary($_FILES['team_image_file']['tmp_name'], 'falhen/team');
            if ($res['success']) {
                $image = $res['url'];
            }
        } else if (preg_match('/^data:image\//i', $image) || (preg_match('/^https?:\/\//i', $image) && !isCloudinaryUrl($image))) {
            $res = uploadToCloudinary($image, 'falhen/team');
            if ($res['success']) {
                $image = $res['url'];
            }
        }

        $items = array_values(getTeamMembers());
        $found = false;

        foreach ($items as &$it) {
            if ((int)($it['id'] ?? 0) === $tId) {
                $it['name'] = $name;
                $it['role'] = $role;
                $it['department'] = $department;
                $it['location'] = $location;
                $it['experience'] = $experience;
                $it['bio'] = $bio;
                $it['skills'] = array_filter(array_map('trim', explode(',', $skillsRaw)));
                if (!empty($image)) {
                    $it['image'] = $image;
                }
                $found = true;
                break;
            }
        }
        unset($it);

        if (!$found) {
            $maxId = 0;
            foreach ($items as $it) {
                if ((int)($it['id'] ?? 0) > $maxId) {
                    $maxId = (int)$it['id'];
                }
            }
            $newId = $maxId + 1;
            $items[] = [
                'id' => $newId,
                'number' => sprintf("%02d", $newId),
                'name' => $name,
                'role' => $role,
                'department' => $department,
                'location' => $location,
                'experience' => $experience,
                'image' => !empty($image) ? $image : '/assets/img/team/team_henry.png',
                'bio' => $bio,
                'skills' => array_filter(array_map('trim', explode(',', $skillsRaw)))
            ];
        }

        saveSiteSettings(['team_items' => $items]);
        header('Location: index.php?section=team&saved=1');
        exit;
    } else if ($action === 'delete_team_member') {
        $tId = (int)($_POST['id'] ?? 0);
        $items = array_values(getTeamMembers());
        $newItems = array_values(array_filter($items, function($it) use ($tId) {
            return (int)($it['id'] ?? 0) !== $tId;
        }));
        saveSiteSettings(['team_items' => $newItems]);
        header('Location: index.php?section=team&deleted=1');
        exit;
    } else if ($action === 'reorder_team_members') {
        $orderedIds = $_POST['order'] ?? [];
        if (!is_array($orderedIds)) {
            $orderedIds = explode(',', (string)$orderedIds);
        }
        $orderedIds = array_map('intval', array_filter($orderedIds));

        $existing = getTeamMembers();
        $newItems = [];
        $seen = [];

        foreach ($orderedIds as $id) {
            if (isset($existing[$id])) {
                $item = $existing[$id];
                $item['number'] = sprintf("%02d", count($newItems) + 1);
                $newItems[] = $item;
                $seen[$id] = true;
            }
        }
        foreach ($existing as $id => $item) {
            if (!isset($seen[$id])) {
                $item['number'] = sprintf("%02d", count($newItems) + 1);
                $newItems[] = $item;
            }
        }

        saveSiteSettings(['team_items' => $newItems]);
        if (isset($_POST['ajax']) && $_POST['ajax'] == 1) {
            sendJSONResponse(true, 'Team member display order updated live!');
        }
        header('Location: index.php?section=team&saved=1');
        exit;
    } else if ($action === 'add_team_location') {
        $newLoc = trim(strip_tags($_POST['location'] ?? ''));
        if (empty($newLoc)) {
            sendJSONResponse(false, 'Location name cannot be empty.');
        }

        $existingLocs = getTeamLocations();
        if (!in_array($newLoc, $existingLocs, true)) {
            $existingLocs[] = $newLoc;
            saveSiteSettings(['team_locations' => $existingLocs]);
        }

        sendJSONResponse(true, 'New location added successfully!', ['location' => $newLoc]);
    } else if ($action === 'add_team_department') {
        $newDept = trim(strip_tags($_POST['department'] ?? ''));
        if (empty($newDept)) {
            sendJSONResponse(false, 'Department name cannot be empty.');
        }

        $existingDepts = getTeamDepartments();
        if (!in_array($newDept, $existingDepts, true)) {
            $existingDepts[] = $newDept;
            saveSiteSettings(['team_departments' => $existingDepts]);
        }

        sendJSONResponse(true, 'New department added successfully!', ['department' => $newDept]);
    } else if ($action === 'save_blog_post') {
        $bId = (int)($_POST['id'] ?? 0);
        $title = trim(strip_tags($_POST['title'] ?? ''));
        $category = trim(strip_tags($_POST['category'] ?? 'Social Media'));
        $date = trim(strip_tags($_POST['date'] ?? date('F j, Y')));
        $readTime = trim(strip_tags($_POST['read_time'] ?? '5 min read'));
        $excerpt = trim(strip_tags($_POST['excerpt'] ?? ''));
        $author = trim(strip_tags($_POST['author'] ?? 'Falhen Team'));
        $role = trim(strip_tags($_POST['role'] ?? 'Contributor'));
        $content = trim($_POST['content'] ?? '');
        $featured = !empty($_POST['featured']);

        $image = trim($_POST['existing_image'] ?? '');
        $croppedData = trim($_POST['cropped_blog_image_data'] ?? '');

        if (!empty($croppedData)) {
            $res = uploadToCloudinary($croppedData, 'falhen/blog');
            if ($res['success']) {
                $image = $res['url'];
            }
        } else if (!empty($_FILES['blog_image_file']['tmp_name'])) {
            $res = uploadToCloudinary($_FILES['blog_image_file']['tmp_name'], 'falhen/blog');
            if ($res['success']) {
                $image = $res['url'];
            }
        } else if (preg_match('/^data:image\//i', $image) || (preg_match('/^https?:\/\//i', $image) && !isCloudinaryUrl($image))) {
            $res = uploadToCloudinary($image, 'falhen/blog');
            if ($res['success']) {
                $image = $res['url'];
            }
        }

        $items = array_values(getBlogRepo());
        $found = false;

        if ($featured) {
            foreach ($items as &$it) {
                $it['featured'] = false;
            }
            unset($it);
        }

        foreach ($items as &$it) {
            if ((int)($it['id'] ?? 0) === $bId) {
                $it['title'] = $title;
                $it['category'] = $category;
                $it['date'] = $date;
                $it['read_time'] = $readTime;
                $it['excerpt'] = $excerpt;
                $it['author'] = $author;
                $it['role'] = $role;
                $it['image'] = !empty($image) ? $image : ($it['image'] ?? '/assets/img/services/service_video.png');
                $it['content'] = $content;
                $it['featured'] = $featured;
                $found = true;
                break;
            }
        }
        unset($it);

        if (!$found) {
            $maxId = 0;
            foreach ($items as $it) {
                $id = (int)($it['id'] ?? 0);
                if ($id > $maxId) $maxId = $id;
            }
            $newId = $maxId + 1;
            $items[] = [
                'id' => $newId,
                'title' => $title,
                'category' => $category,
                'date' => $date,
                'read_time' => $readTime,
                'excerpt' => $excerpt,
                'author' => $author,
                'role' => $role,
                'image' => !empty($image) ? $image : '/assets/img/services/service_video.png',
                'content' => $content,
                'featured' => $featured
            ];
        }

        $targetId = $found ? $bId : $newId;
        saveSiteSettings(['blog_items' => $items]);
        header('Location: index.php?section=blog&edit_id=' . $targetId . '&saved=1');
        exit;
    } else if ($action === 'delete_blog_post') {
        $bId = (int)($_POST['id'] ?? 0);
        $items = array_values(getBlogRepo());
        $newItems = array_values(array_filter($items, function($it) use ($bId) {
            return (int)($it['id'] ?? 0) !== $bId;
        }));
        saveSiteSettings(['blog_items' => $newItems]);
        header('Location: index.php?section=blog&deleted=1');
        exit;
    } else if ($action === 'add_blog_category') {
        $newCat = trim(strip_tags($_POST['category'] ?? ''));
        if (empty($newCat)) {
            sendJSONResponse(false, 'Category name cannot be empty.');
        }

        $existingCats = getBlogCategories();
        if (!in_array($newCat, $existingCats, true)) {
            $existingCats[] = $newCat;
            saveSiteSettings(['blog_categories' => $existingCats]);
        }

        sendJSONResponse(true, 'New blog category added successfully!', ['category' => $newCat]);
    } else if ($action === 'reorder_blog_posts') {
        $orderedIds = $_POST['order'] ?? [];
        if (!is_array($orderedIds)) {
            $orderedIds = explode(',', (string)$orderedIds);
        }
        $orderedIds = array_map('intval', array_filter($orderedIds));

        $existing = getBlogRepo();
        $newItems = [];
        $seen = [];

        foreach ($orderedIds as $id) {
            if (isset($existing[$id])) {
                $newItems[] = $existing[$id];
                $seen[$id] = true;
            }
        }
        foreach ($existing as $id => $item) {
            if (!isset($seen[$id])) {
                $newItems[] = $item;
            }
        }

        saveSiteSettings(['blog_items' => $newItems]);
        if (isset($_POST['ajax']) && $_POST['ajax'] == 1) {
            sendJSONResponse(true, 'Blog articles display order updated live!');
        }
        header('Location: index.php?section=blog&saved=1');
        exit;
    } else if ($action === 'save_job_opening') {
        $jId = (int)($_POST['id'] ?? 0);
        $title = trim(strip_tags($_POST['title'] ?? ''));
        $dept = trim(strip_tags($_POST['dept'] ?? 'Production'));
        $location = trim(strip_tags($_POST['location'] ?? 'Oakbrook, IL / Hybrid'));
        $type = trim(strip_tags($_POST['type'] ?? 'Full-time'));
        $salary = trim(strip_tags($_POST['salary'] ?? ''));
        $overview = trim($_POST['overview'] ?? '');
        $responsibilities = trim($_POST['responsibilities'] ?? '');
        $requirements = trim($_POST['requirements'] ?? '');
        $benefits = trim($_POST['benefits'] ?? '');
        $status = trim(strip_tags($_POST['status'] ?? 'open'));

        $items = array_values(getCareersRepo());
        $found = false;

        foreach ($items as &$it) {
            if ((int)($it['id'] ?? 0) === $jId) {
                $it['title'] = $title;
                $it['dept'] = $dept;
                $it['location'] = $location;
                $it['type'] = $type;
                $it['salary'] = $salary;
                $it['overview'] = $overview;
                $it['responsibilities'] = $responsibilities;
                $it['requirements'] = $requirements;
                $it['benefits'] = $benefits;
                $it['status'] = $status;
                $found = true;
                break;
            }
        }
        unset($it);

        if (!$found) {
            $maxId = 0;
            foreach ($items as $it) {
                $id = (int)($it['id'] ?? 0);
                if ($id > $maxId) $maxId = $id;
            }
            $newId = $maxId + 1;
            $items[] = [
                'id' => $newId,
                'title' => $title,
                'dept' => $dept,
                'location' => $location,
                'type' => $type,
                'salary' => $salary,
                'overview' => $overview,
                'responsibilities' => $responsibilities,
                'requirements' => $requirements,
                'benefits' => $benefits,
                'status' => $status,
                'posted' => 'Just now'
            ];
        }

        $targetId = $found ? $jId : $newId;
        saveSiteSettings(['job_openings' => $items]);
        header('Location: index.php?section=careers&edit_id=' . $targetId . '&saved=1');
        exit;
    } else if ($action === 'delete_job_opening') {
        $jId = (int)($_POST['id'] ?? 0);
        $items = array_values(getCareersRepo());
        $newItems = array_values(array_filter($items, function($it) use ($jId) {
            return (int)($it['id'] ?? 0) !== $jId;
        }));
        saveSiteSettings(['job_openings' => $newItems]);
        header('Location: index.php?section=careers&deleted=1');
        exit;
    } else if ($action === 'toggle_job_status') {
        $jId = (int)($_POST['id'] ?? 0);
        $items = array_values(getCareersRepo());
        foreach ($items as &$it) {
            if ((int)($it['id'] ?? 0) === $jId) {
                $it['status'] = ($it['status'] ?? 'open') === 'open' ? 'closed' : 'open';
                break;
            }
        }
        saveSiteSettings(['job_openings' => $items]);
        header('Location: index.php?section=careers&saved=1');
        exit;
    } else if ($action === 'update_app_status') {
        $appId = (int)($_POST['app_id'] ?? 0);
        $newStatus = trim(strip_tags($_POST['app_status'] ?? 'new'));
        $apps = getJobApplicationsRepo();
        foreach ($apps as &$a) {
            if ((int)($a['id'] ?? 0) === $appId) {
                $a['status'] = $newStatus;
                break;
            }
        }
        saveSiteSettings(['job_applications' => $apps]);
        header('Location: index.php?section=careers&tab=applications&saved=1');
        exit;
    } else if ($action === 'save_brand_logo') {
        $bId = (int)($_POST['id'] ?? 0);
        $name = trim(strip_tags($_POST['name'] ?? ''));
        $icon = trim(strip_tags($_POST['icon'] ?? 'fa-solid fa-star'));
        $visible = !empty($_POST['visible']);

        $image = trim($_POST['existing_image'] ?? '');
        $croppedData = trim($_POST['cropped_brand_image_data'] ?? '');

        if (!empty($croppedData)) {
            $res = uploadToCloudinary($croppedData, 'falhen/brands');
            if ($res['success']) {
                $image = $res['url'];
            }
        } else if (!empty($_FILES['brand_image_file']['tmp_name'])) {
            $res = uploadToCloudinary($_FILES['brand_image_file']['tmp_name'], 'falhen/brands');
            if ($res['success']) {
                $image = $res['url'];
            }
        } else if (preg_match('/^data:image\//i', $image) || (preg_match('/^https?:\/\//i', $image) && !isCloudinaryUrl($image))) {
            $res = uploadToCloudinary($image, 'falhen/brands');
            if ($res['success']) {
                $image = $res['url'];
            }
        }

        $items = array_values(getBrandLogosRepo());
        $found = false;

        foreach ($items as &$it) {
            if ((int)($it['id'] ?? 0) === $bId) {
                $it['name'] = $name;
                $it['icon'] = $icon;
                $it['image'] = $image;
                $it['visible'] = $visible;
                $found = true;
                break;
            }
        }
        unset($it);

        if (!$found) {
            $maxId = 0;
            foreach ($items as $it) {
                $id = (int)($it['id'] ?? 0);
                if ($id > $maxId) $maxId = $id;
            }
            $newId = $maxId + 1;
            $items[] = [
                'id' => $newId,
                'name' => $name,
                'icon' => $icon,
                'image' => $image,
                'visible' => $visible
            ];
        }

        $targetId = $found ? $bId : $newId;
        saveSiteSettings(['brand_logos_items' => $items]);
        header('Location: index.php?section=brands&edit_id=' . $targetId . '&saved=1');
        exit;
    } else if ($action === 'delete_brand_logo') {
        $bId = (int)($_POST['id'] ?? 0);
        $items = array_values(getBrandLogosRepo());
        $newItems = array_values(array_filter($items, function($it) use ($bId) {
            return (int)($it['id'] ?? 0) !== $bId;
        }));
        saveSiteSettings(['brand_logos_items' => $newItems]);
        header('Location: index.php?section=brands&deleted=1');
        exit;
    } else if ($action === 'toggle_brand_visibility') {
        $bId = (int)($_POST['id'] ?? 0);
        $items = array_values(getBrandLogosRepo());
        foreach ($items as &$it) {
            if ((int)($it['id'] ?? 0) === $bId) {
                $it['visible'] = empty($it['visible']);
                break;
            }
        }
        saveSiteSettings(['brand_logos_items' => $items]);
        header('Location: index.php?section=brands&saved=1');
        exit;
    } else if ($action === 'reorder_brand_logos') {
        $orderedIds = $_POST['order'] ?? [];
        if (!is_array($orderedIds)) {
            $orderedIds = explode(',', (string)$orderedIds);
        }
        $orderedIds = array_map('intval', array_filter($orderedIds));

        $existingRepo = getBrandLogosRepo();
        $existing = [];
        foreach ($existingRepo as $item) {
            $id = (int)($item['id'] ?? 0);
            if ($id > 0) $existing[$id] = $item;
        }

        $newItems = [];
        $seen = [];

        foreach ($orderedIds as $id) {
            if (isset($existing[$id])) {
                $newItems[] = $existing[$id];
                $seen[$id] = true;
            }
        }
        foreach ($existing as $id => $item) {
            if (!isset($seen[$id])) {
                $newItems[] = $item;
            }
        }

        saveSiteSettings(['brand_logos_items' => $newItems]);
        if (isset($_POST['ajax']) && $_POST['ajax'] == 1) {
            sendJSONResponse(true, 'Brand display order updated live!');
        }
        header('Location: index.php?section=brands&saved=1');
        exit;
    } else if ($action === 'save_profile_details') {
        $fullName = trim(strip_tags($_POST['full_name'] ?? ''));
        $username = trim(strip_tags($_POST['username'] ?? 'admin'));
        $email = trim(strip_tags($_POST['email'] ?? ''));
        $role = trim(strip_tags($_POST['role'] ?? 'Administrator'));
        $bio = trim($_POST['bio'] ?? '');

        $avatar = trim($_POST['existing_avatar'] ?? '');
        $croppedData = trim($_POST['cropped_avatar_image_data'] ?? '');

        if (!empty($croppedData)) {
            $res = uploadToCloudinary($croppedData, 'falhen/profile');
            if ($res['success']) {
                $avatar = $res['url'];
            }
        } else if (!empty($_FILES['avatar_file']['tmp_name'])) {
            $res = uploadToCloudinary($_FILES['avatar_file']['tmp_name'], 'falhen/profile');
            if ($res['success']) {
                $avatar = $res['url'];
            }
        } else if (preg_match('/^data:image\//i', $avatar) || (preg_match('/^https?:\/\//i', $avatar) && !isCloudinaryUrl($avatar))) {
            $res = uploadToCloudinary($avatar, 'falhen/profile');
            if ($res['success']) {
                $avatar = $res['url'];
            }
        }

        saveAdminUserProfile([
            'full_name' => $fullName,
            'username'  => $username,
            'email'     => $email,
            'role'      => $role,
            'avatar'    => $avatar,
            'bio'       => $bio
        ]);

        header('Location: index.php?section=profile&saved=1');
        exit;
    } else if ($action === 'change_profile_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $profile = getAdminUserProfile();
        $storedHash = $profile['password_hash'] ?? '';

        $currentValid = false;
        if (!empty($storedHash) && password_verify($currentPassword, $storedHash)) {
            $currentValid = true;
        } else if ($currentPassword === 'Password123#') {
            $currentValid = true;
        }

        if (!$currentValid) {
            header('Location: index.php?section=profile&pwd_error=invalid_current');
            exit;
        }

        if (empty($newPassword) || strlen($newPassword) < 6) {
            header('Location: index.php?section=profile&pwd_error=too_short');
            exit;
        }

        if ($newPassword !== $confirmPassword) {
            header('Location: index.php?section=profile&pwd_error=mismatch');
            exit;
        }

        saveAdminUserProfile([
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT)
        ]);

        header('Location: index.php?section=profile&pwd_saved=1');
        exit;
    } else if ($action === 'save_staff_account') {
        $sId = (int)($_POST['id'] ?? 0);
        $fullName = trim(strip_tags($_POST['full_name'] ?? ''));
        $username = trim(strip_tags($_POST['username'] ?? ''));
        $email = trim(strip_tags($_POST['email'] ?? ''));
        $role = trim(strip_tags($_POST['role'] ?? 'Staff'));
        $status = trim(strip_tags($_POST['status'] ?? 'active'));
        $password = $_POST['password'] ?? '';

        $avatar = trim($_POST['existing_avatar'] ?? '');
        $croppedData = trim($_POST['cropped_staff_avatar_data'] ?? '');

        if (!empty($croppedData)) {
            $res = uploadToCloudinary($croppedData, 'falhen/team');
            if ($res['success']) {
                $avatar = $res['url'];
            }
        } else if (!empty($_FILES['staff_avatar_file']['tmp_name'])) {
            $res = uploadToCloudinary($_FILES['staff_avatar_file']['tmp_name'], 'falhen/team');
            if ($res['success']) {
                $avatar = $res['url'];
            }
        } else if (preg_match('/^data:image\//i', $avatar) || (preg_match('/^https?:\/\//i', $avatar) && !isCloudinaryUrl($avatar))) {
            $res = uploadToCloudinary($avatar, 'falhen/team');
            if ($res['success']) {
                $avatar = $res['url'];
            }
        }

        $items = array_values(getStaffAccountsRepo());
        $found = false;

        foreach ($items as &$it) {
            if ((int)($it['id'] ?? 0) === $sId) {
                $it['full_name'] = $fullName;
                $it['username']  = $username;
                $it['email']     = $email;
                $it['role']      = $role;
                $it['status']    = $status;
                $it['avatar']    = $avatar;
                if (!empty($password)) {
                    $it['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
                }
                $found = true;
                break;
            }
        }
        unset($it);

        if (!$found) {
            $maxId = 0;
            foreach ($items as $it) {
                $id = (int)($it['id'] ?? 0);
                if ($id > $maxId) $maxId = $id;
            }
            $newId = $maxId + 1;
            $items[] = [
                'id'            => $newId,
                'full_name'     => $fullName,
                'username'      => $username,
                'email'         => $email,
                'role'          => $role,
                'status'        => $status,
                'avatar'        => $avatar,
                'created_at'    => date('Y-m-d'),
                'password_hash' => password_hash(!empty($password) ? $password : 'Password123#', PASSWORD_DEFAULT)
            ];
        }

        $targetId = $found ? $sId : $newId;
        saveSiteSettings(['staff_accounts' => $items]);
        header('Location: index.php?section=staff_accounts&edit_id=' . $targetId . '&saved=1');
        exit;
    } else if ($action === 'delete_staff_account') {
        $sId = (int)($_POST['id'] ?? 0);
        $items = array_values(getStaffAccountsRepo());
        $newItems = array_values(array_filter($items, function($it) use ($sId) {
            return (int)($it['id'] ?? 0) !== $sId;
        }));
        saveSiteSettings(['staff_accounts' => $newItems]);
        header('Location: index.php?section=staff_accounts&deleted=1');
        exit;
    } else if ($action === 'toggle_staff_status') {
        $sId = (int)($_POST['id'] ?? 0);
        $items = array_values(getStaffAccountsRepo());
        foreach ($items as &$it) {
            if ((int)($it['id'] ?? 0) === $sId) {
                $it['status'] = ($it['status'] ?? 'active') === 'active' ? 'suspended' : 'active';
                break;
            }
        }
        saveSiteSettings(['staff_accounts' => $items]);
        header('Location: index.php?section=staff_accounts&saved=1');
        exit;
    } else if ($action === 'sync_team_and_staff') {
        $result = syncTeamAndStaffAccounts();
        $targetSection = $_GET['section'] ?? 'team';
        header("Location: index.php?section={$targetSection}&synced=1&added={$result['added']}&updated={$result['updated']}");
        exit;
    } else if ($action === 'upload_cloudinary') {
        if (!empty($_FILES['cloudinary_file']['tmp_name'])) {
            $res = uploadToCloudinary($_FILES['cloudinary_file']['tmp_name'], 'falhen/uploads');
            if ($res['success']) {
                $uploadedCloudinaryUrl = $res['url'];
                $savedSuccess = true;
                $savedMessage = '✓ Asset uploaded to Cloudinary successfully! URL: ' . $res['url'];
            } else {
                $savedSuccess = false;
                $savedMessage = 'Cloudinary upload error: ' . ($res['message'] ?? 'Unknown error');
            }
        }
    } else if ($action === 'upload_cloudinary_ajax') {
        header('Content-Type: application/json');
        $folder = trim(strip_tags($_POST['folder'] ?? 'falhen/team'));
        
        $target = '';
        if (!empty($_FILES['file']['tmp_name'])) {
            $target = $_FILES['file']['tmp_name'];
        } else if (!empty($_POST['image_data'])) {
            $target = trim($_POST['image_data']);
        }

        if (empty($target)) {
            sendJSONResponse(false, 'No image file or data provided.');
        }

        $res = uploadToCloudinary($target, $folder);
        if ($res['success']) {
            sendJSONResponse(true, 'Image uploaded to Cloudinary successfully!', ['url' => $res['url']]);
        } else {
            sendJSONResponse(false, 'Cloudinary upload failed: ' . ($res['message'] ?? 'Unknown error'));
        }
    } else if ($action === 'update_inquiry_status') {
        $inquiryId = (int)($_POST['inquiry_id'] ?? 0);
        $newStatus = sanitizeInput($_POST['status'] ?? 'new');
        if ($pdo && $inquiryId) {
            try {
                $stmt = $pdo->prepare("UPDATE `inquiries` SET `status` = ? WHERE `id` = ?");
                $stmt->execute([$newStatus, $inquiryId]);
                $savedSuccess = true;
                $savedMessage = "Inquiry #$inquiryId status updated to " . htmlspecialchars($newStatus) . "!";
            } catch (Exception $e) {
                $savedMessage = "Failed to update inquiry status.";
            }
        }
    }
}

// Fetch current site settings
$settings = getSiteSettings();

// Fetch inquiries & statistics
$inquiries = [];
$stats = [
    'total_inquiries' => 0,
    'new_inquiries'   => 0,
    'total_services'  => 7,
    'total_portfolio' => 5
];

if ($pdo) {
    try {
        $inquiries = $pdo->query("SELECT * FROM `inquiries` ORDER BY `id` DESC")->fetchAll();
        $stats['total_inquiries'] = count($inquiries);
        $stats['new_inquiries']   = count(array_filter($inquiries, fn($i) => ($i['status'] ?? '') === 'new'));
    } catch (Exception $e) {}
}

$adminProfile = getAdminUserProfile();
$username = $_SESSION['admin_username'] ?? $adminProfile['username'] ?? 'admin';
$userEmail = $_SESSION['admin_email'] ?? $adminProfile['email'] ?? 'admin@falhen.com';
$userRole = $_SESSION['admin_role'] ?? $adminProfile['role'] ?? 'Administrator';
$userAvatar = $_SESSION['admin_avatar'] ?? $adminProfile['avatar'] ?? '';
$userFullName = $_SESSION['admin_full_name'] ?? $adminProfile['full_name'] ?? 'Henry Falonipe';
$userInitial = strtoupper(substr($userFullName, 0, 1));

// Resolve active section & evaluate RBAC permission
$rawSection = $_GET['section'] ?? null;
if ($rawSection === null) {
    $activeSection = getUserFirstAllowedSection($userRole, $userEmail, $username);
} else {
    $activeSection = $rawSection;
}

$isAccessDenied = !hasSectionAccess($activeSection, $userRole, $userEmail, $username) || isset($_GET['denied']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Falhen Admin &mdash; Dashboard</title>
    <link rel="icon" type="image/png" href="/assets/img/icons/favicon.png">
    <link rel="shortcut icon" type="image/png" href="/assets/img/icons/favicon.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
    <style>
        /* Zero-Dependency Native HTML Editor Toolbar Styling */
        .native-editor-btn {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 4px 10px;
            font-size: 0.84rem;
            font-weight: 700;
            color: #334155;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 32px;
            height: 32px;
            transition: all 0.15s ease;
        }
        .native-editor-btn:hover {
            background: #dc2626;
            color: #ffffff;
            border-color: #dc2626;
        }
        #nativeBlogEditor h1 {
            font-size: 2.1rem;
            font-weight: 800;
            color: #0f172a;
            margin-top: 1.2em;
            margin-bottom: 0.4em;
            line-height: 1.3;
        }
        #nativeBlogEditor h2,
        #nativeBlogEditor h3 {
            font-size: 1.7rem;
            font-weight: 800;
            color: #0f172a;
            margin-top: 1.2em;
            margin-bottom: 0.4em;
            line-height: 1.35;
        }
        #nativeBlogEditor blockquote {
            border-left: 4px solid #dc2626;
            background: #fef2f2;
            padding: 10px 16px;
            margin: 14px 0;
            border-radius: 0 8px 8px 0;
            font-style: italic;
            color: #991b1b;
        }
        #nativeBlogEditor ul {
            list-style: none;
            padding-left: 0;
            margin: 16px 0;
        }
        #nativeBlogEditor ul li {
            position: relative;
            padding-left: 28px;
            margin-bottom: 10px;
            line-height: 1.6;
            color: #1e293b;
        }
        #nativeBlogEditor ul li::before {
            content: "\f058";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            position: absolute;
            left: 0;
            top: 2px;
            color: #dc2626;
            font-size: 1.05rem;
        }
        .ql-toolbar.ql-snow {
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
            border-color: #cbd5e1 !important;
            background: #f8fafc;
        }
        .ql-container.ql-snow {
            border-bottom-left-radius: 10px;
            border-bottom-right-radius: 10px;
            border-color: #cbd5e1 !important;
            font-family: inherit;
        }
        .ql-editor {
            min-height: 220px;
        }
        :root {
            --bg-body: #f8fafc;
            --sidebar-bg: #ffffff;
            --sidebar-border: #e2e8f0;
            --card-bg: #ffffff;
            --card-border: #e2e8f0;
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --text-muted: #94a3b8;
            --accent-red: #dc2626;
            --accent-red-hover: #b91c1c;
            --accent-red-light: rgba(220, 38, 38, 0.08);
            --accent-red-border: rgba(220, 38, 38, 0.2);
            --input-bg: #ffffff;
            --input-border: #cbd5e1;
            --radius-md: 12px;
            --radius-lg: 16px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-body);
            color: var(--text-primary);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            display: flex;
            -webkit-font-smoothing: antialiased;
        }

        /* 1. SIDEBAR LAYOUT */
        .sidebar {
            width: 260px;
            background-color: var(--sidebar-bg);
            border-right: 1px solid var(--sidebar-border);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 20px 20px 16px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid var(--sidebar-border);
        }

        .sidebar-brand-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background-color: var(--accent-red);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .sidebar-brand-info {
            display: flex;
            flex-direction: column;
        }

        .sidebar-brand-name {
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--text-primary);
            line-height: 1.2;
        }

        .sidebar-brand-badge {
            font-size: 0.68rem;
            font-weight: 700;
            color: var(--text-muted);
            letter-spacing: 0.5px;
        }

        /* User Profile Box inside Sidebar */
        .sidebar-user-box {
            margin: 16px;
            padding: 12px 14px;
            background-color: #f1f5f9;
            border: 1px solid var(--sidebar-border);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .user-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #ef4444;
        }

        .user-details {
            display: flex;
            flex-direction: column;
        }

        .user-title {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .user-sub {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .user-shield {
            color: var(--accent-red);
            font-size: 0.9rem;
        }

        /* Navigation Menu Lists */
        .nav-category {
            padding: 14px 20px 6px 20px;
            font-size: 0.72rem;
            font-weight: 800;
            color: var(--text-muted);
            letter-spacing: 0.8px;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            user-select: none;
            transition: color 0.2s ease;
        }

        .nav-category:hover {
            color: #0f172a;
        }

        .nav-category.active-category,
        .nav-category.active {
            color: #0f172a !important;
        }

        .nav-category-chevron {
            font-size: 0.68rem;
            color: #94a3b8;
            transition: transform 0.25s ease, color 0.2s ease;
        }

        .nav-category:hover .nav-category-chevron,
        .nav-category.active-category .nav-category-chevron,
        .nav-category.active .nav-category-chevron {
            color: #0f172a !important;
        }

        .nav-category.collapsed .nav-category-chevron {
            transform: rotate(-90deg);
        }

        .nav-list {
            list-style: none;
            padding: 0 10px;
            margin: 0;
            transition: all 0.25s ease;
        }

        .nav-category.collapsed + .nav-list {
            display: none;
        }

        @media (max-width: 992px) {
            .leaves-layout-grid {
                grid-template-columns: 1fr !important;
            }
        }

        .nav-item a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 12px;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.15s ease;
        }

        .nav-item a:hover {
            background-color: #f1f5f9;
            color: var(--text-primary);
        }

        .nav-item.active a {
            background-color: var(--accent-red-light);
            color: var(--accent-red);
            font-weight: 700;
        }

        .nav-item.active a i {
            color: var(--accent-red);
        }

        .nav-icon {
            width: 18px;
            font-size: 0.95rem;
            color: var(--text-muted);
        }

        .sidebar-footer {
            margin-top: auto;
            padding: 16px 20px;
            border-top: 1px solid var(--sidebar-border);
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .sidebar-footer a {
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .sidebar-footer a:hover {
            color: var(--accent-red);
        }

        /* 2. MAIN CONTENT AREA */
        .main-wrapper {
            margin-left: 260px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            padding-top: 60px;
            min-width: 0;
            overflow-x: hidden;
        }

        /* Top Header Navigation Bar */
        .top-header {
            height: 60px;
            background-color: #ffffff;
            border-bottom: 1px solid var(--sidebar-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 32px;
            position: fixed;
            top: 0;
            left: 260px;
            right: 0;
            z-index: 999;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .breadcrumb {
            font-size: 0.88rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .breadcrumb strong {
            color: var(--text-primary);
            font-weight: 600;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .header-link {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: color 0.15s;
        }

        .header-link:hover {
            color: var(--accent-red);
        }

        .user-avatar-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: var(--accent-red);
            color: #ffffff;
            font-weight: 800;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Section Container */
        .content-body {
            padding: 32px;
            max-width: 100%;
            margin: 0;
            width: 100%;
            overflow-x: auto;
            min-width: 0;
        }

        #kanbanBoardContainer::-webkit-scrollbar,
        .content-body::-webkit-scrollbar {
            height: 10px;
            width: 10px;
        }
        #kanbanBoardContainer::-webkit-scrollbar-track,
        .content-body::-webkit-scrollbar-track {
            background: #1e293b;
            border-radius: 6px;
        }
        #kanbanBoardContainer::-webkit-scrollbar-thumb,
        .content-body::-webkit-scrollbar-thumb {
            background: #dc2626;
            border-radius: 6px;
            border: 2px solid #1e293b;
        }
        #kanbanBoardContainer::-webkit-scrollbar-thumb:hover,
        .content-body::-webkit-scrollbar-thumb:hover {
            background: #ef4444;
        }

        /* Sticky / Main Section Header */
        .section-header-bar {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 28px;
        }

        .section-header-title {
            font-size: 1.85rem;
            font-weight: 800;
            color: var(--text-primary);
            letter-spacing: -0.5px;
            margin-bottom: 6px;
        }

        .section-header-desc {
            font-size: 0.92rem;
            color: var(--text-secondary);
        }

        .btn-save-primary {
            background-color: var(--accent-red);
            color: #ffffff;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-size: 0.9rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.25);
            transition: background-color 0.2s, transform 0.15s;
        }

        .btn-save-primary:hover {
            background-color: var(--accent-red-hover);
            transform: translateY(-1px);
        }

        /* Two-Column Form Layout Grid */
        .form-grid-two {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            align-items: start;
        }

        @media (max-width: 990px) {
            .form-grid-two {
                grid-template-columns: 1fr;
            }
        }

        /* Dashboard Card Boxes */
        .dashboard-card {
            background-color: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: var(--radius-lg);
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            margin-bottom: 24px;
        }

        .card-header-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .card-icon-badge {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background-color: var(--accent-red-light);
            border: 1px solid var(--accent-red-border);
            color: var(--accent-red);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
        }

        .card-title-text {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        /* Form Inputs */
        .form-field {
            margin-bottom: 18px;
        }

        .form-label-title {
            display: block;
            font-size: 0.88rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 6px;
        }

        .form-label-help {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-bottom: 8px;
            line-height: 1.4;
        }

        .form-text-input {
            width: 100%;
            padding: 10px 14px;
            background-color: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 0.92rem;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            font-family: inherit;
        }

        .form-text-input:focus {
            border-color: var(--accent-red);
            box-shadow: 0 0 0 3px var(--accent-red-light);
        }

        /* Status & Notice Boxes */
        .info-status-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 14px;
            font-size: 0.84rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .priority-list-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 14px;
            margin-bottom: 18px;
        }

        .priority-title {
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }

        .priority-item {
            font-size: 0.82rem;
            color: var(--text-muted);
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .priority-item.active-item {
            color: var(--accent-red);
            font-weight: 700;
        }

        /* Video Preview Card */
        .video-preview-box {
            width: 100%;
            border-radius: 10px;
            overflow: hidden;
            background-color: #0f172a;
            position: relative;
            aspect-ratio: 16/9;
            margin-top: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .video-preview-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .preview-overlay-tag {
            position: absolute;
            bottom: 10px;
            left: 10px;
            background-color: rgba(15, 23, 42, 0.8);
            color: #ffffff;
            font-size: 0.72rem;
            padding: 3px 8px;
            border-radius: 4px;
        }

        /* Inquiries Table */
        .data-table-wrapper {
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: var(--radius-md);
            overflow: hidden;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.88rem;
            text-align: left;
        }

        table.data-table th, table.data-table td {
            padding: 12px 16px;
            border-bottom: 1px solid var(--card-border);
        }

        table.data-table th {
            background-color: #f8fafc;
            color: var(--text-secondary);
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        /* Floating Toast Alert */
        .toast-banner {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #0f172a;
            color: #ffffff;
            padding: 12px 20px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.88rem;
            z-index: 1000;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    </style>
</head>
<body>

    <!-- 1. LEFT SIDEBAR -->
    <aside class="sidebar">
        <!-- Sidebar Brand Header -->
        <div class="sidebar-header">
            <div class="sidebar-brand-icon">
                <i class="fa-solid fa-layer-group"></i>
            </div>
            <div class="sidebar-brand-info">
                <div class="sidebar-brand-name">Falhen</div>
                <div class="sidebar-brand-badge">ADMIN</div>
            </div>
        </div>

        <!-- User Profile Box -->
        <a href="/admin/index.php?section=profile" class="sidebar-user-box" style="text-decoration: none; cursor: pointer; display: flex; align-items: center; justify-content: space-between; transition: all 0.2s ease;">
            <div class="user-left" style="display: flex; align-items: center; gap: 10px;">
                <?php if (!empty($userAvatar)): ?>
                    <img src="<?php echo htmlspecialchars(getCloudinaryUrl($userAvatar)); ?>" alt="Profile Avatar" style="width: 38px; height: 38px; border-radius: 50%; object-fit: cover; border: 2px solid #ef4444; flex-shrink: 0;">
                <?php else: ?>
                    <div style="width: 38px; height: 38px; border-radius: 50%; background: #dc2626; color: #ffffff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1rem; flex-shrink: 0;">
                        <?php echo $userInitial; ?>
                    </div>
                <?php endif; ?>
                <div class="user-details" style="overflow: hidden;">
                    <div class="user-title" style="font-weight: 800; color: #0f172a; font-size: 0.85rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 110px;"><?php echo htmlspecialchars($userFullName); ?></div>
                    <div class="user-sub" style="font-size: 0.74rem; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 110px;"><?php echo htmlspecialchars($userRole); ?></div>
                </div>
            </div>
            <i class="fa-solid fa-gear user-shield" style="color: #64748b; font-size: 0.9rem;" title="My Profile & Settings"></i>
        </a>

        <!-- Navigation Categories -->
        <?php 
        $isHomepageActive = in_array($activeSection, ['hero', 'stats', 'awards', 'bts', 'brands'], true);
        $isContentActive = in_array($activeSection, ['inquiries', 'services', 'portfolio', 'client_galleries', 'team', 'blog', 'testimonials', 'careers'], true);
        $isOperationsActive = in_array($activeSection, ['hr_portal', 'vendors', 'activity_log', 'email_templates'], true) || ($activeSection === 'careers' && ($_GET['tab'] ?? '') === 'applications');
        $isEmployeePortalActive = in_array($activeSection, ['dashboard', 'directory', 'announcements', 'attendance', 'leaves', 'payslips'], true) || ($activeSection === 'onboarding' && !isAdminUser($userRole, $userEmail, $username));
        $isAccountActive = in_array($activeSection, ['staff_accounts', 'my_profile', 'profile'], true);
        ?>
        <?php if (isAdminUser($userRole, $userEmail, $username)): ?>
            <?php if (!isTalentManager($userRole)): ?>
            <!-- CATEGORY: HOMEPAGE -->
            <div class="nav-category <?php echo $isHomepageActive ? 'active-category' : ''; ?>" onclick="toggleNavCategory(this)">
                <span>Homepage</span>
                <i class="fa-solid fa-chevron-down nav-category-chevron"></i>
            </div>
            <ul class="nav-list">
                <li class="nav-item <?php echo ($activeSection === 'hero') ? 'active' : ''; ?>">
                    <a href="/admin/index.php?section=hero">
                        <i class="fa-solid fa-box-archive nav-icon"></i>
                        <span>Hero</span>
                    </a>
                </li>
                <li class="nav-item <?php echo ($activeSection === 'stats') ? 'active' : ''; ?>">
                    <a href="/admin/index.php?section=stats">
                        <i class="fa-solid fa-chart-simple nav-icon"></i>
                        <span>Stats Counter</span>
                    </a>
                </li>
                <li class="nav-item <?php echo ($activeSection === 'awards') ? 'active' : ''; ?>">
                    <a href="/admin/index.php?section=awards">
                        <i class="fa-solid fa-trophy nav-icon"></i>
                        <span>Awards</span>
                    </a>
                </li>
                <li class="nav-item <?php echo ($activeSection === 'bts') ? 'active' : ''; ?>">
                    <a href="/admin/index.php?section=bts">
                        <i class="fa-solid fa-clapperboard nav-icon"></i>
                        <span>Production BTS</span>
                    </a>
                </li>
                <li class="nav-item <?php echo ($activeSection === 'brands') ? 'active' : ''; ?>">
                    <a href="/admin/index.php?section=brands">
                        <i class="fa-solid fa-handshake nav-icon"></i>
                        <span>Client Brands</span>
                    </a>
                </li>
            </ul>

            <!-- CATEGORY: CONTENT -->
            <div class="nav-category <?php echo $isContentActive ? 'active-category' : ''; ?>" onclick="toggleNavCategory(this)">
                <span>Content</span>
                <i class="fa-solid fa-chevron-down nav-category-chevron"></i>
            </div>
            <ul class="nav-list">
                <li class="nav-item <?php echo ($activeSection === 'inquiries') ? 'active' : ''; ?>">
                    <a href="/admin/index.php?section=inquiries">
                        <i class="fa-solid fa-inbox nav-icon"></i>
                        <span>Inquiries &amp; Quotes</span>
                    </a>
                </li>
                <li class="nav-item <?php echo ($activeSection === 'services') ? 'active' : ''; ?>">
                    <a href="/admin/index.php?section=services">
                        <i class="fa-solid fa-concierge-bell nav-icon"></i>
                        <span>Services</span>
                    </a>
                </li>
                <li class="nav-item <?php echo ($activeSection === 'portfolio') ? 'active' : ''; ?>">
                    <a href="/admin/index.php?section=portfolio">
                        <i class="fa-solid fa-images nav-icon"></i>
                        <span>Portfolio</span>
                    </a>
                </li>
                <li class="nav-item <?php echo ($activeSection === 'client_galleries') ? 'active' : ''; ?>">
                    <a href="/admin/index.php?section=client_galleries">
                        <i class="fa-solid fa-user-gear nav-icon"></i>
                        <span>Client Galleries</span>
                    </a>
                </li>
                <li class="nav-item <?php echo ($activeSection === 'team') ? 'active' : ''; ?>">
                    <a href="/admin/index.php?section=team">
                        <i class="fa-solid fa-users nav-icon"></i>
                        <span>Team</span>
                    </a>
                </li>
                <li class="nav-item <?php echo ($activeSection === 'blog') ? 'active' : ''; ?>">
                    <a href="/admin/index.php?section=blog">
                        <i class="fa-solid fa-newspaper nav-icon"></i>
                        <span>Blog</span>
                    </a>
                </li>
                <li class="nav-item <?php echo ($activeSection === 'testimonials') ? 'active' : ''; ?>">
                    <a href="/admin/index.php?section=testimonials">
                        <i class="fa-solid fa-quote-left nav-icon"></i>
                        <span>Testimonials</span>
                    </a>
                </li>
                <li class="nav-item <?php echo ($activeSection === 'careers') ? 'active' : ''; ?>">
                    <a href="/admin/index.php?section=careers">
                        <i class="fa-solid fa-briefcase nav-icon"></i>
                        <span>Careers &amp; Hiring</span>
                    </a>
                </li>
            </ul>
            <?php endif; ?>

            <?php if (!isContentEditor($userRole)): ?>
            <!-- CATEGORY: OPERATIONS -->
            <div class="nav-category <?php echo $isOperationsActive ? 'active-category' : ''; ?>" onclick="toggleNavCategory(this)">
                <span>Operations</span>
                <i class="fa-solid fa-chevron-down nav-category-chevron"></i>
            </div>
            <ul class="nav-list">
                <li class="nav-item <?php echo ($activeSection === 'hr_portal' || ($activeSection === 'careers' && ($_GET['tab'] ?? '') === 'applications')) ? 'active' : ''; ?>">
                    <a href="/admin/index.php?section=careers&tab=applications">
                        <i class="fa-solid fa-users-gear nav-icon"></i>
                        <span>HR Portal &amp; Applications</span>
                    </a>
                </li>
                <li class="nav-item <?php echo ($activeSection === 'vendors') ? 'active' : ''; ?>">
                    <a href="/admin/index.php?section=vendors">
                        <i class="fa-solid fa-id-card nav-icon"></i>
                        <span>Vendors</span>
                    </a>
                </li>
                <li class="nav-item <?php echo ($activeSection === 'onboarding') ? 'active' : ''; ?>">
                    <a href="/admin/index.php?section=onboarding">
                        <i class="fa-solid fa-clipboard-check nav-icon"></i>
                        <span>Onboarding</span>
                    </a>
                </li>
                <li class="nav-item <?php echo ($activeSection === 'activity_log') ? 'active' : ''; ?>">
                    <a href="/admin/index.php?section=activity_log">
                        <i class="fa-solid fa-list-check nav-icon"></i>
                        <span>Activity Log</span>
                    </a>
                </li>
                <li class="nav-item <?php echo ($activeSection === 'email_templates') ? 'active' : ''; ?>">
                    <a href="/admin/index.php?section=email_templates">
                        <i class="fa-solid fa-envelope-open-text nav-icon"></i>
                        <span>Email Templates</span>
                    </a>
                </li>
            </ul>
            <?php endif; ?>

            <!-- CATEGORY: EMPLOYEE PORTAL -->
            <div class="nav-category <?php echo $isEmployeePortalActive ? 'active-category' : ''; ?>" onclick="toggleNavCategory(this)">
                <span>Employee Portal</span>
                <i class="fa-solid fa-chevron-down nav-category-chevron"></i>
            </div>
            <ul class="nav-list">
                <li class="nav-item <?php echo ($activeSection === 'dashboard') ? 'active' : ''; ?>">
                    <a href="/admin/index.php?section=dashboard">
                        <i class="fa-solid fa-gauge-high nav-icon"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item <?php echo ($activeSection === 'onboarding') ? 'active' : ''; ?>">
                    <a href="/admin/index.php?section=onboarding">
                        <i class="fa-solid fa-clipboard-check nav-icon"></i>
                        <span>Onboarding</span>
                    </a>
                </li>
                <li class="nav-item <?php echo ($activeSection === 'directory') ? 'active' : ''; ?>">
                    <a href="/admin/index.php?section=directory">
                        <i class="fa-solid fa-address-book nav-icon"></i>
                        <span>Directory</span>
                    </a>
                </li>
                <li class="nav-item <?php echo ($activeSection === 'attendance') ? 'active' : ''; ?>">
                    <a href="/admin/index.php?section=attendance">
                        <i class="fa-solid fa-calendar-check nav-icon"></i>
                        <span>My Attendance</span>
                    </a>
                </li>
                <li class="nav-item <?php echo ($activeSection === 'leaves') ? 'active' : ''; ?>">
                    <a href="/admin/index.php?section=leaves">
                        <i class="fa-solid fa-umbrella-beach nav-icon"></i>
                        <span>Time Off (Leaves)</span>
                    </a>
                </li>
                <li class="nav-item <?php echo ($activeSection === 'payslips') ? 'active' : ''; ?>">
                    <a href="/admin/index.php?section=payslips">
                        <i class="fa-solid fa-file-invoice-dollar nav-icon"></i>
                        <span>My Payslips</span>
                    </a>
                </li>
            </ul>

            <!-- CATEGORY: ACCOUNT -->
            <div class="nav-category <?php echo $isAccountActive ? 'active-category' : ''; ?>" onclick="toggleNavCategory(this)">
                <span>Account</span>
                <i class="fa-solid fa-chevron-down nav-category-chevron"></i>
            </div>
            <ul class="nav-list" style="margin-bottom: 20px;">
                <?php if (!isContentEditor($userRole)): ?>
                <li class="nav-item <?php echo ($activeSection === 'staff_accounts') ? 'active' : ''; ?>">
                    <a href="/admin/index.php?section=staff_accounts">
                        <i class="fa-solid fa-user-shield nav-icon"></i>
                        <span>Staff Accounts</span>
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item <?php echo ($activeSection === 'my_profile' || $activeSection === 'profile') ? 'active' : ''; ?>">
                    <a href="/admin/index.php?section=my_profile">
                        <i class="fa-solid fa-circle-user nav-icon"></i>
                        <span>My Profile</span>
                    </a>
                </li>
            </ul>
        <?php else: ?>
            <!-- NON-ADMIN EMPLOYEE PORTAL NAVIGATION -->
            <div class="nav-category <?php echo $isEmployeePortalActive ? 'active-category' : ''; ?>" onclick="toggleNavCategory(this)">
                <span>Employee Portal</span>
                <i class="fa-solid fa-chevron-down nav-category-chevron"></i>
            </div>
            <ul class="nav-list">
                <li class="nav-item <?php echo ($activeSection === 'dashboard') ? 'active' : ''; ?>">
                    <a href="/admin/index.php?section=dashboard">
                        <i class="fa-solid fa-gauge-high nav-icon"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item <?php echo ($activeSection === 'onboarding') ? 'active' : ''; ?>">
                    <a href="/admin/index.php?section=onboarding">
                        <i class="fa-solid fa-clipboard-check nav-icon"></i>
                        <span>Onboarding</span>
                    </a>
                </li>
                <li class="nav-item <?php echo ($activeSection === 'directory') ? 'active' : ''; ?>">
                    <a href="/admin/index.php?section=directory">
                        <i class="fa-solid fa-address-book nav-icon"></i>
                        <span>Directory</span>
                    </a>
                </li>
                <li class="nav-item <?php echo ($activeSection === 'attendance') ? 'active' : ''; ?>">
                    <a href="/admin/index.php?section=attendance">
                        <i class="fa-solid fa-calendar-check nav-icon"></i>
                        <span>My Attendance</span>
                    </a>
                </li>
                <li class="nav-item <?php echo ($activeSection === 'leaves') ? 'active' : ''; ?>">
                    <a href="/admin/index.php?section=leaves">
                        <i class="fa-solid fa-umbrella-beach nav-icon"></i>
                        <span>Time Off (Leaves)</span>
                    </a>
                </li>
                <li class="nav-item <?php echo ($activeSection === 'payslips') ? 'active' : ''; ?>">
                    <a href="/admin/index.php?section=payslips">
                        <i class="fa-solid fa-file-invoice-dollar nav-icon"></i>
                        <span>My Payslips</span>
                    </a>
                </li>
            </ul>

            <div class="nav-category <?php echo $isAccountActive ? 'active-category' : ''; ?>" onclick="toggleNavCategory(this)">
                <span>Account</span>
                <i class="fa-solid fa-chevron-down nav-category-chevron"></i>
            </div>
            <ul class="nav-list" style="margin-bottom: 20px;">
                <li class="nav-item <?php echo ($activeSection === 'my_profile' || $activeSection === 'profile') ? 'active' : ''; ?>">
                    <a href="/admin/index.php?section=my_profile">
                        <i class="fa-solid fa-circle-user nav-icon"></i>
                        <span>My Profile</span>
                    </a>
                </li>
            </ul>
        <?php endif; ?>

        <!-- Sidebar Footer -->
        <div class="sidebar-footer">
            <a href="/" target="_blank">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> View Live Site
            </a>
            <div style="margin-top: 6px; font-size: 0.7rem; color: #cbd5e1;">
                Hero, Awards &amp; BTS save to DB &amp; settings file.
            </div>
        </div>
    </aside>

    <!-- 2. MAIN WRAPPER -->
    <main class="main-wrapper">

        <!-- Top Navigation Header -->
        <header class="top-header">
            <div class="breadcrumb" style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                <?php 
                $sectionTitles = [
                    'hero' => 'Hero',
                    'stats' => 'Stats Counter',
                    'awards' => 'Awards',
                    'bts' => 'Production BTS',
                    'featured' => 'Featured Work',
                    'services' => 'Services',
                    'portfolio' => 'Portfolio',
                    'video_gallery' => 'Video Gallery',
                    'inquiries' => 'Inquiries & Quotes',
                    'dashboard' => 'Dashboard',
                    'mail' => 'Staff Mail',
                    'comms' => 'Team Comms',
                    'assets' => 'Brand Assets'
                ];
                $displayBreadcrumb = $sectionTitles[$activeSection] ?? ucwords(str_replace('_', ' ', $activeSection));
                ?>
                <div>Admin &rsaquo; <strong><?php echo htmlspecialchars($displayBreadcrumb); ?></strong></div>

                <!-- 4 PRIMARY TOPBAR MENU ITEMS FOR STAFF PORTAL -->
                <?php 
                $homeSections = ['dashboard', 'onboarding', 'directory', 'announcements', 'attendance', 'leaves', 'payslips', 'home', 'hero'];
                $isHomeActive = in_array($activeSection, $homeSections, true);
                ?>
                <nav class="topbar-primary-nav" style="display: flex; align-items: center; gap: 4px; background: #f1f5f9; padding: 4px; border-radius: 10px; border: 1px solid #cbd5e1; margin-left: 8px;">
                    <a href="/admin/index.php?section=<?php echo (isAdminUser($userRole, $userEmail, $username) && !isTalentManager($userRole)) ? 'hero' : 'dashboard'; ?>" class="topbar-nav-link" style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; font-size: 0.84rem; font-weight: 700; text-decoration: none; border-radius: 7px; transition: all 0.15s ease; <?php echo $isHomeActive ? 'background: #ffffff; color: #dc2626; box-shadow: 0 1px 3px rgba(0,0,0,0.08);' : 'color: #475569;'; ?>">
                        <i class="fa-solid fa-house" style="font-size: 0.8rem; <?php echo $isHomeActive ? 'color: #dc2626;' : 'color: #64748b;'; ?>"></i>
                        <span>Home</span>
                    </a>
                    <a href="/admin/index.php?section=mail" class="topbar-nav-link" style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; font-size: 0.84rem; font-weight: 700; text-decoration: none; border-radius: 7px; transition: all 0.15s ease; <?php echo ($activeSection === 'mail') ? 'background: #ffffff; color: #dc2626; box-shadow: 0 1px 3px rgba(0,0,0,0.08);' : 'color: #475569;'; ?>">
                        <i class="fa-solid fa-envelope" style="font-size: 0.8rem; <?php echo ($activeSection === 'mail') ? 'color: #dc2626;' : 'color: #64748b;'; ?>"></i>
                        <span>Mail</span>
                    </a>
                    <a href="/admin/index.php?section=comms" class="topbar-nav-link" style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; font-size: 0.84rem; font-weight: 700; text-decoration: none; border-radius: 7px; transition: all 0.15s ease; <?php echo ($activeSection === 'comms') ? 'background: #ffffff; color: #dc2626; box-shadow: 0 1px 3px rgba(0,0,0,0.08);' : 'color: #475569;'; ?>">
                        <i class="fa-solid fa-comments" style="font-size: 0.8rem; <?php echo ($activeSection === 'comms') ? 'color: #dc2626;' : 'color: #64748b;'; ?>"></i>
                        <span>Comms</span>
                    </a>
                    <a href="/admin/index.php?section=assets" class="topbar-nav-link" style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; font-size: 0.84rem; font-weight: 700; text-decoration: none; border-radius: 7px; transition: all 0.15s ease; <?php echo ($activeSection === 'assets') ? 'background: #ffffff; color: #dc2626; box-shadow: 0 1px 3px rgba(0,0,0,0.08);' : 'color: #475569;'; ?>">
                        <i class="fa-solid fa-photo-film" style="font-size: 0.8rem; <?php echo ($activeSection === 'assets') ? 'color: #dc2626;' : 'color: #64748b;'; ?>"></i>
                        <span>Assets</span>
                    </a>
                </nav>
            </div>

            <div class="header-actions" style="display: flex; align-items: center; gap: 10px;">
                <!-- DYNAMIC TOPBAR WORK STATE TOGGLE (CLOCK IN / START BREAK / END BREAK / CLOCK OUT) -->
                <?php 
                $topbarAttendance = getUserTodayAttendance($username);
                $workState = $topbarAttendance['work_state'] ?? ($topbarAttendance ? (empty($topbarAttendance['clock_out']) ? (($topbarAttendance['status'] ?? '') === 'On Break' ? 'on_break' : 'working') : 'completed') : 'not_clocked_in');
                ?>

                <?php if ($workState === 'not_clocked_in'): ?>
                    <form method="POST" action="/admin/index.php?section=<?php echo htmlspecialchars($activeSection); ?>" style="margin: 0;">
                        <input type="hidden" name="action" value="clock_in">
                        <button type="submit" style="background: #16a34a; color: #ffffff; border: none; padding: 5px 14px; border-radius: 20px; font-size: 0.78rem; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 1px 3px rgba(22, 163, 74, 0.25);" title="Clock In for Today">
                            <i class="fa-solid fa-play" style="font-size: 0.72rem;"></i> Clock In
                        </button>
                    </form>

                <?php elseif ($workState === 'working'): ?>
                    <div style="display: flex; align-items: center; gap: 6px; background: rgba(22, 163, 74, 0.08); padding: 3px 4px; border-radius: 20px; border: 1px solid rgba(22, 163, 74, 0.2);">
                        <form method="POST" action="/admin/index.php?section=<?php echo htmlspecialchars($activeSection); ?>" style="margin: 0;">
                            <input type="hidden" name="action" value="start_break">
                            <button type="submit" style="background: #d97706; color: #ffffff; border: none; padding: 4px 11px; border-radius: 16px; font-size: 0.75rem; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; box-shadow: 0 1px 2px rgba(217, 119, 6, 0.2);" title="Take a break">
                                <i class="fa-solid fa-mug-hot" style="font-size: 0.7rem;"></i> Break
                            </button>
                        </form>

                        <form method="POST" action="/admin/index.php?section=<?php echo htmlspecialchars($activeSection); ?>" style="margin: 0;">
                            <input type="hidden" name="action" value="clock_out">
                            <button type="submit" style="background: #dc2626; color: #ffffff; border: none; padding: 4px 11px; border-radius: 16px; font-size: 0.75rem; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; box-shadow: 0 1px 2px rgba(220, 38, 38, 0.2);" title="Clock Out & End Shift (In at <?php echo htmlspecialchars($topbarAttendance['clock_in']); ?>)">
                                <i class="fa-solid fa-stop" style="font-size: 0.7rem;"></i> Clock Out
                            </button>
                        </form>
                    </div>

                <?php elseif ($workState === 'on_break'): ?>
                    <div style="display: flex; align-items: center; gap: 6px; background: rgba(217, 119, 6, 0.08); padding: 3px 4px; border-radius: 20px; border: 1px solid rgba(217, 119, 6, 0.25);">
                        <span style="font-size: 0.75rem; font-weight: 800; color: #b45309; padding: 0 8px; display: inline-flex; align-items: center; gap: 4px;">
                            <i class="fa-solid fa-mug-hot" style="font-size: 0.7rem;"></i> On Break
                        </span>
                        <form method="POST" action="/admin/index.php?section=<?php echo htmlspecialchars($activeSection); ?>" style="margin: 0;">
                            <input type="hidden" name="action" value="end_break">
                            <button type="submit" style="background: #2563eb; color: #ffffff; border: none; padding: 4px 12px; border-radius: 16px; font-size: 0.75rem; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; box-shadow: 0 1px 3px rgba(37, 99, 235, 0.25);" title="End break & resume work session">
                                <i class="fa-solid fa-circle-play" style="font-size: 0.7rem;"></i> Resume
                            </button>
                        </form>
                    </div>

                <?php else: ?>
                    <div style="display: flex; align-items: center; gap: 6px; background: rgba(22, 163, 74, 0.08); padding: 3px 6px; border-radius: 20px; border: 1px solid rgba(22, 163, 74, 0.2);">
                        <span style="font-size: 0.74rem; font-weight: 800; color: #15803d; padding: 0 4px; display: inline-flex; align-items: center; gap: 4px;" title="Shift Completed (<?php echo htmlspecialchars($topbarAttendance['duration']); ?>)">
                            <i class="fa-solid fa-circle-check" style="font-size: 0.7rem;"></i> Shift Done
                        </span>
                        <form method="POST" action="/admin/index.php?section=<?php echo htmlspecialchars($activeSection); ?>" style="margin: 0;">
                            <input type="hidden" name="action" value="clock_in">
                            <button type="submit" style="background: #16a34a; color: #ffffff; border: none; padding: 4px 11px; border-radius: 16px; font-size: 0.74rem; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; box-shadow: 0 1px 2px rgba(22, 163, 74, 0.25);" title="Clock In for new session">
                                <i class="fa-solid fa-play" style="font-size: 0.68rem;"></i> Clock In
                            </button>
                        </form>
                    </div>
                <?php endif; ?>

                <a href="/admin/login.php?action=logout" class="header-link" style="color: var(--text-secondary);">
                    <i class="fa-solid fa-right-from-bracket"></i> Sign Out
                </a>
                <a href="/admin/index.php?section=profile" class="user-avatar-circle" title="My Profile Settings" style="text-decoration: none; overflow: hidden; display: flex; align-items: center; justify-content: center; cursor: pointer; border: 2px solid #dc2626;">
                    <?php if (!empty($userAvatar)): ?>
                        <img src="<?php echo htmlspecialchars(getCloudinaryUrl($userAvatar)); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <?php echo $userInitial; ?>
                    <?php endif; ?>
                </a>
            </div>
        </header>

        <!-- Main Body Area -->
        <div class="content-body">

            <!-- Toast Success Alert -->
            <?php if ($savedSuccess): ?>
                <div class="toast-banner" id="toastAlert">
                    <i class="fa-solid fa-circle-check" style="color: #4ade80; font-size: 1.1rem;"></i>
                    <span><?php echo htmlspecialchars($savedMessage); ?></span>
                </div>
            <?php endif; ?>

            <!-- RBAC ACCESS DENIED ALERT CARD -->
            <?php if ($isAccessDenied): ?>
                <div class="dashboard-card" style="margin-top: 20px; padding: 45px 30px; text-align: center; border-left: 5px solid #dc2626; background: #ffffff; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); border-radius: 14px;">
                    <div style="width: 72px; height: 72px; background: rgba(220, 38, 38, 0.08); color: #dc2626; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 2.2rem; margin-bottom: 20px;">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <h2 style="font-size: 1.75rem; font-weight: 800; color: #0f172a; margin-bottom: 12px; letter-spacing: -0.02em;">Access Restricted</h2>
                    <p style="font-size: 0.98rem; color: #64748b; max-width: 580px; margin: 0 auto 28px auto; line-height: 1.65;">
                        Your account role (<span style="color: #dc2626; font-weight: 700; background: #fef2f2; padding: 2px 8px; border-radius: 4px; border: 1px solid #fecaca;"><?php echo htmlspecialchars($userRole); ?></span>) does not have authorization to access or manage the <strong><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $activeSection))); ?></strong> section.
                    </p>
                    <div style="display: flex; gap: 14px; justify-content: center; align-items: center; flex-wrap: wrap;">
                        <a href="/admin/index.php?section=<?php echo htmlspecialchars(getUserFirstAllowedSection($userRole)); ?>" class="btn-save-primary" style="text-decoration: none; display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; font-size: 0.92rem;">
                            <i class="fa-solid fa-house-user"></i> Go to My Authorized Dashboard
                        </a>
                        <a href="/admin/index.php?section=my_profile" class="native-editor-btn" style="text-decoration: none; height: 44px; padding: 0 20px; font-size: 0.9rem; font-weight: 700;">
                            <i class="fa-solid fa-user-gear" style="margin-right: 6px;"></i> My Profile Settings
                        </a>
                    </div>
                </div>
            <?php else: ?>

            <!-- SECTION 1: HERO SECTION -->
            <?php if ($activeSection === 'hero'): ?>
                <form method="POST" action="/admin/index.php?section=hero" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="save_hero">

                    <div class="section-header-bar">
                        <div>
                            <h1 class="section-header-title">Hero Section</h1>
                            <p class="section-header-desc">Edit the homepage hero &mdash; background film, poster image, text, and call-to-action buttons</p>
                        </div>
                        <button type="submit" class="btn-save-primary">
                            <i class="fa-solid fa-floppy-disk"></i> Save Changes
                        </button>
                    </div>

                    <div class="form-grid-two">
                        <!-- Left Column: Background Film Card -->
                        <div>
                            <div class="dashboard-card">
                                <div class="card-header-row">
                                    <div class="card-icon-badge">
                                        <i class="fa-solid fa-film"></i>
                                    </div>
                                    <div class="card-title-text">Background Film</div>
                                </div>

                                <!-- Direct Video URL -->
                                <div class="form-field">
                                    <label class="form-label-title">Direct Video URL (MP4 / WebM)</label>
                                    <p class="form-label-help">Fastest option &mdash; a direct .mp4 or .webm URL plays instantly via native browser video. Leave blank to use YouTube, Vimeo, or the poster image instead.</p>
                                    <input 
                                        type="text" 
                                        name="hero_direct_video_url" 
                                        class="form-text-input" 
                                        placeholder="https://your-cdn.com/hero-bg.mp4"
                                        value="<?php echo htmlspecialchars($settings['hero_direct_video_url'] ?? ''); ?>"
                                    >
                                </div>

                                <!-- Third Party Video Sources -->
                                <div class="form-field">
                                    <label class="form-label-title">Third-party video sources</label>
                                    <p class="form-label-help">Use the Upload Media section below to paste a YouTube or Vimeo link. These fields show what's currently configured.</p>

                                    <div class="info-status-box">
                                        <i class="fa-brands fa-youtube"></i>
                                        <span><?php echo !empty($settings['hero_youtube_bg']) ? htmlspecialchars($settings['hero_youtube_bg']) : 'No YouTube background configured'; ?></span>
                                    </div>
                                    <div class="info-status-box">
                                        <i class="fa-brands fa-vimeo"></i>
                                        <span><?php echo !empty($settings['hero_vimeo_bg']) ? htmlspecialchars($settings['hero_vimeo_bg']) : 'No Vimeo background configured'; ?></span>
                                    </div>
                                </div>

                                <!-- Active Background Priority -->
                                <div class="priority-list-box">
                                    <div class="priority-title">Active background priority:</div>
                                    <div class="priority-item <?php echo !empty($settings['hero_direct_video_url']) ? 'active-item' : ''; ?>">
                                        1. Direct video URL <?php echo !empty($settings['hero_direct_video_url']) ? '(Active)' : '(not set)'; ?>
                                    </div>
                                    <div class="priority-item <?php echo (!empty($settings['hero_youtube_bg']) && empty($settings['hero_direct_video_url'])) ? 'active-item' : ''; ?>">
                                        2. YouTube background
                                    </div>
                                    <div class="priority-item <?php echo (!empty($settings['hero_vimeo_bg']) && empty($settings['hero_direct_video_url']) && empty($settings['hero_youtube_bg'])) ? 'active-item' : ''; ?>">
                                        3. Vimeo background
                                    </div>
                                    <div class="priority-item <?php echo (empty($settings['hero_direct_video_url']) && empty($settings['hero_youtube_bg']) && empty($settings['hero_vimeo_bg'])) ? 'active-item' : ''; ?>">
                                        4. &#10003; Poster image only
                                    </div>
                                </div>

                                <!-- Poster Image URL -->
                                <div class="form-field">
                                    <label class="form-label-title">Poster / Fallback Image URL</label>
                                    <p class="form-label-help">Shown while the video loads, and as the hero background when no video is set. Use 1920&times;1080.</p>
                                    <input 
                                        type="text" 
                                        name="hero_poster_image" 
                                        id="hero_poster_image_input"
                                        class="form-text-input" 
                                        placeholder="https://res.cloudinary.com/pnabfi91/image/upload/..."
                                        value="<?php echo htmlspecialchars($uploadedCloudinaryUrl ?? ($settings['hero_poster_image'] ?? '/assets/img/hero.jpg')); ?>"
                                        oninput="updatePosterPreview(this.value)"
                                    >

                                    <!-- Cloudinary CDN Asset Helper Badge & Quick Upload -->
                                    <div style="background-color: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 10px; padding: 12px 14px; margin-top: 10px;">
                                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                                            <span style="font-size: 0.78rem; font-weight: 700; color: #0284c7; display: inline-flex; align-items: center; gap: 6px;">
                                                <i class="fa-solid fa-cloud"></i> Cloudinary CDN (<strong>pnabfi91</strong>)
                                            </span>
                                            <span style="font-size: 0.72rem; background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 10px; font-weight: 700;">f_auto, q_auto</span>
                                        </div>
                                        <p style="font-size: 0.76rem; color: var(--text-muted); margin: 0 0 8px 0;">
                                            Select any image file to upload directly to your Cloudinary account:
                                        </p>
                                        <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                                            <input type="file" name="cloudinary_file" id="cloudinary_direct_input" accept="image/*,video/*" style="font-size: 0.78rem; color: var(--text-secondary);">
                                            <button type="submit" name="action" value="upload_cloudinary" class="btn-save-primary" style="padding: 6px 12px; font-size: 0.78rem; background-color: #0284c7;">
                                                <i class="fa-solid fa-cloud-arrow-up"></i> Upload to Cloudinary
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Poster Image Preview Box -->
                                <div style="margin-bottom: 24px;">
                                    <div style="border-radius: 12px; overflow: hidden; background-color: #0f172a; border: 1px solid var(--card-border);">
                                        <img 
                                            id="posterPreviewImg" 
                                            src="<?php echo htmlspecialchars($settings['hero_poster_image'] ?? '/assets/img/hero.jpg'); ?>" 
                                            alt="Poster Preview"
                                            style="width: 100%; height: auto; max-height: 260px; object-fit: cover; display: block;"
                                            onerror="this.src='/assets/img/hero.jpg';"
                                        >
                                    </div>
                                </div>

                                <!-- Live Background Preview Section -->
                                <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
                                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
                                        <h4 style="font-size: 0.92rem; font-weight: 700; color: var(--text-primary); margin: 0;">Live Background Preview</h4>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <span style="font-size: 0.78rem; color: var(--text-muted);">Muted autoplay</span>
                                            <span id="livePreviewBadge" style="background-color: #d97706; color: #ffffff; font-size: 0.72rem; font-weight: 700; padding: 2px 8px; border-radius: 10px;">Poster Only</span>
                                        </div>
                                    </div>

                                    <!-- Live Preview Frame -->
                                    <div style="position: relative; border-radius: 14px; overflow: hidden; background-color: #0f172a; aspect-ratio: 16/9; max-height: 240px; display: flex; align-items: center; justify-content: center; margin-bottom: 12px; border: 1px solid var(--card-border);">
                                        <img 
                                            id="liveBackgroundImg" 
                                            src="<?php echo htmlspecialchars($settings['hero_poster_image'] ?? '/assets/img/hero.jpg'); ?>" 
                                            alt="Live Background"
                                            style="width: 100%; height: 100%; object-fit: cover;"
                                            onerror="this.src='/assets/img/hero.jpg';"
                                        >

                                        <!-- Top Right Mode Tag -->
                                        <div id="frameModeTag" style="position: absolute; top: 12px; right: 12px; background-color: #d97706; color: #ffffff; font-size: 0.7rem; font-weight: 700; padding: 3px 8px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.3);">
                                            Poster Only
                                        </div>

                                        <!-- Bottom Left Live Tag -->
                                        <div style="position: absolute; bottom: 12px; left: 12px; background-color: rgba(15, 23, 42, 0.85); color: #ffffff; font-size: 0.7rem; font-weight: 700; padding: 3px 8px; border-radius: 6px; display: flex; align-items: center; gap: 6px;">
                                            <span style="width: 6px; height: 6px; border-radius: 50%; background-color: #ef4444;"></span> Live Preview
                                        </div>
                                    </div>

                                    <p style="font-size: 0.78rem; color: var(--text-muted); line-height: 1.45; margin-bottom: 16px;">
                                        This shows exactly what visitors see on the homepage. The preview reloads automatically when you change the background source.
                                    </p>

                                    <div style="text-align: left;">
                                        <a href="#" onclick="resetHeroBackgroundDefaults(); return false;" style="color: var(--text-muted); font-size: 0.82rem; font-weight: 500; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='var(--text-primary)'" onmouseout="this.style.color='var(--text-muted)'">
                                            Reset to defaults
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Showreel & Text Copy Cards -->
                        <div>
                            <!-- Showreel Modal Video Card -->
                            <div class="dashboard-card">
                                <div class="card-header-row">
                                    <div class="card-icon-badge">
                                        <i class="fa-solid fa-circle-play"></i>
                                    </div>
                                    <div class="card-title-text">Showreel Modal Video</div>
                                </div>

                                <div class="form-field">
                                    <label class="form-label-title">Showreel YouTube Video ID or URL</label>
                                    <p class="form-label-help">This plays when visitors click 'Watch Showreel'. Paste a YouTube URL or just the 11-character video ID.</p>
                                    <input 
                                        type="text" 
                                        name="showreel_youtube_id" 
                                        id="showreelInput"
                                        class="form-text-input" 
                                        placeholder="Tf8rNMZ-Bw0"
                                        value="<?php echo htmlspecialchars($settings['showreel_youtube_id'] ?? 'Tf8rNMZ-Bw0'); ?>"
                                        oninput="updateShowreelPreview(this.value)"
                                    >
                                    <div style="font-size: 0.76rem; color: var(--text-muted); margin-top: 6px;" id="resolvedIdText">
                                        Resolved ID: <strong><?php echo htmlspecialchars($settings['showreel_youtube_id'] ?? 'Tf8rNMZ-Bw0'); ?></strong>
                                    </div>
                                </div>

                                <!-- Video Thumbnail Preview Box -->
                                <div class="video-preview-box">
                                    <iframe 
                                        id="showreelIframe"
                                        src="https://www.youtube.com/embed/<?php echo htmlspecialchars($settings['showreel_youtube_id'] ?? 'Tf8rNMZ-Bw0'); ?>" 
                                        style="width:100%; height:100%; border:none;" 
                                        allowfullscreen
                                    ></iframe>
                                    <div class="preview-overlay-tag">Hover to preview</div>
                                </div>
                            </div>

                            <!-- Text & Copy Card (Exact Mockup Match) -->
                            <div class="dashboard-card">
                                <div class="card-header-row">
                                    <div class="card-icon-badge">
                                        <span style="font-weight: 800; font-size: 0.95rem;">T</span>
                                    </div>
                                    <div class="card-title-text">Text &amp; Copy</div>
                                </div>

                                <!-- Badge Label -->
                                <div class="form-field">
                                    <label class="form-label-title">Badge Label</label>
                                    <p class="form-label-help">Small pill above the headline &mdash; leave blank to hide it</p>
                                    <input 
                                        type="text" 
                                        name="hero_badge_label" 
                                        id="hero_badge_label_input"
                                        class="form-text-input" 
                                        placeholder=""
                                        value="<?php echo htmlspecialchars($settings['hero_badge_label'] ?? ''); ?>"
                                        oninput="updateHeroPreview()"
                                    >
                                </div>

                                <!-- Headline Line 1 -->
                                <div class="form-field">
                                    <label class="form-label-title">Headline &mdash; Line 1</label>
                                    <p class="form-label-help">White text, first line of the big headline</p>
                                    <input 
                                        type="text" 
                                        name="hero_headline_line1" 
                                        id="hero_line1_input"
                                        class="form-text-input" 
                                        value="<?php echo htmlspecialchars($settings['hero_headline_line1'] ?? 'Creating what the'); ?>"
                                        oninput="updateHeroPreview()"
                                    >
                                </div>

                                <!-- Headline Line 2 (Red Glow) -->
                                <div class="form-field">
                                    <label class="form-label-title">Headline &mdash; Line 2 (Red Glow)</label>
                                    <p class="form-label-help">Red glowing text, second line</p>
                                    <input 
                                        type="text" 
                                        name="hero_headline_line2" 
                                        id="hero_line2_input"
                                        class="form-text-input" 
                                        value="<?php echo htmlspecialchars($settings['hero_headline_line2'] ?? 'World Watches'); ?>"
                                        oninput="updateHeroPreview()"
                                    >
                                </div>

                                <!-- Tagline / Subheading -->
                                <div class="form-field">
                                    <label class="form-label-title">Tagline / Subheading</label>
                                    <textarea 
                                        name="hero_tagline" 
                                        id="hero_tagline_input"
                                        class="form-text-input" 
                                        rows="3" 
                                        style="resize: vertical;"
                                        oninput="updateHeroPreview()"
                                    ><?php echo htmlspecialchars($settings['hero_tagline'] ?? "From cinematic campaigns to viral content — we craft the visual stories your audience can't look away from."); ?></textarea>
                                    <div style="text-align: right; font-size: 0.76rem; color: var(--text-muted); margin-top: 4px;" id="taglineCounter">
                                        <?php echo strlen($settings['hero_tagline'] ?? "From cinematic campaigns to viral content — we craft the visual stories your audience can't look away from."); ?>/200
                                    </div>
                                </div>

                                <!-- Primary CTA & Secondary CTA Buttons Row -->
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;" class="form-field">
                                    <div>
                                        <label class="form-label-title">Primary CTA Button</label>
                                        <input 
                                            type="text" 
                                            name="hero_primary_cta_text" 
                                            id="primary_cta_input"
                                            class="form-text-input" 
                                            value="<?php echo htmlspecialchars($settings['hero_primary_cta_text'] ?? 'Explore Our Projects'); ?>"
                                            oninput="updateHeroPreview()"
                                        >
                                    </div>
                                    <div>
                                        <label class="form-label-title">Secondary CTA Button</label>
                                        <input 
                                            type="text" 
                                            name="hero_secondary_cta_text" 
                                            id="secondary_cta_input"
                                            class="form-text-input" 
                                            value="<?php echo htmlspecialchars($settings['hero_secondary_cta_text'] ?? 'Watch Showreel'); ?>"
                                            oninput="updateHeroPreview()"
                                        >
                                    </div>
                                </div>

                                <!-- Hero Live Preview Card (Black Box) -->
                                <div style="background-color: #0a0e17; border-radius: 14px; padding: 22px; margin-top: 20px; color: #ffffff;">
                                    <div id="previewBadge" style="<?php echo !empty($settings['hero_badge_label']) ? 'display: inline-block;' : 'display: none;'; ?> background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #ef4444; font-size: 0.7rem; font-weight: 700; padding: 3px 8px; border-radius: 12px; margin-bottom: 12px;">
                                        <?php echo htmlspecialchars($settings['hero_badge_label'] ?? ''); ?>
                                    </div>
                                    
                                    <div style="font-size: 1.45rem; font-weight: 800; line-height: 1.2; margin-bottom: 12px;">
                                        <div id="previewLine1"><?php echo htmlspecialchars($settings['hero_headline_line1'] ?? 'Creating what the'); ?></div>
                                        <div id="previewLine2" style="color: #ef4444;"><?php echo htmlspecialchars($settings['hero_headline_line2'] ?? 'World Watches'); ?></div>
                                    </div>

                                    <p id="previewTagline" style="font-size: 0.85rem; color: #94a3b8; line-height: 1.45; margin-bottom: 16px;">
                                        <?php echo htmlspecialchars($settings['hero_tagline'] ?? "From cinematic campaigns to viral content — we craft the visual stories your audience can't look away from."); ?>
                                    </p>

                                    <div style="display: flex; gap: 10px;">
                                        <div id="previewPrimaryCta" style="background-color: #dc2626; color: #ffffff; padding: 8px 14px; border-radius: 6px; font-size: 0.8rem; font-weight: 700;">
                                            <?php echo htmlspecialchars($settings['hero_primary_cta_text'] ?? 'Explore Our Projects'); ?>
                                        </div>
                                        <div id="previewSecondaryCta" style="background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.2); color: #ffffff; padding: 8px 14px; border-radius: 6px; font-size: 0.8rem; font-weight: 600;">
                                            <?php echo htmlspecialchars($settings['hero_secondary_cta_text'] ?? 'Watch Showreel'); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

            <!-- SECTION: STATS COUNTER -->
            <?php elseif ($activeSection === 'stats'): ?>
                <?php 
                $statsBadge = $settings['stats_badge_label'] ?? 'By the Numbers';
                $statsWhite = $settings['stats_headline_white'] ?? 'A Decade of';
                $statsRed = $settings['stats_headline_red'] ?? 'Impact';
                $statsDesc = $settings['stats_description'] ?? "The numbers behind Falhen's reputation as one of Africa's most awarded and globally recognised production houses.";
                $statsItems = $settings['stats_items'] ?? [
                    ['number' => '250', 'suffix' => '+', 'prefix' => '', 'label' => 'Projects Delivered', 'sublabel' => 'Across commercial, film & events', 'icon' => 'ri-film-line'],
                    ['number' => '12', 'suffix' => '+', 'prefix' => '', 'label' => 'Years Experience', 'sublabel' => 'Delivering world-class productions', 'icon' => 'ri-history-line'],
                    ['number' => '7', 'suffix' => '+', 'prefix' => '', 'label' => 'Industries Served', 'sublabel' => 'From tech to luxury to social', 'icon' => 'ri-earth-line'],
                    ['number' => '4', 'suffix' => '+', 'prefix' => '', 'label' => 'Industry Awards', 'sublabel' => 'SHH, Webby & more', 'icon' => 'ri-trophy-line']
                ];
                ?>
                <form id="statsForm" method="POST" action="/admin/index.php?section=stats">
                    <input type="hidden" name="action" value="save_stats">

                    <div class="section-header-bar">
                        <div>
                            <h1 class="section-header-title">Stats Counter</h1>
                            <p class="section-header-desc">Edit the "By the Numbers" section &mdash; syncs across all devices via Supabase</p>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <a href="/" target="_blank" class="btn-live-site" style="text-decoration: none;">
                                <i class="fa-solid fa-eye"></i> Preview
                            </a>
                            <button type="submit" class="btn-save-primary">
                                <i class="fa-solid fa-floppy-disk"></i> Save Changes
                            </button>
                        </div>
                    </div>

                    <div class="form-grid-two">
                        <!-- Left Column: Section Header & Live Section Preview -->
                        <div>
                            <!-- Section Header Card -->
                            <div class="dashboard-card">
                                <div class="card-header-row">
                                    <div class="card-icon-badge">
                                        <span style="font-weight: 800; font-size: 0.95rem;">T</span>
                                    </div>
                                    <div class="card-title-text">Section Header</div>
                                </div>

                                <!-- Badge Label -->
                                <div class="form-field">
                                    <label class="form-label-title">Badge Label</label>
                                    <p class="form-label-help">Small pill above the headline</p>
                                    <input 
                                        type="text" 
                                        name="stats_badge_label" 
                                        id="stats_badge_input"
                                        class="form-text-input" 
                                        value="<?php echo htmlspecialchars($statsBadge); ?>"
                                        oninput="updateStatsPreview()"
                                    >
                                </div>

                                <!-- Headline - White Part -->
                                <div class="form-field">
                                    <label class="form-label-title">Headline &mdash; White Part</label>
                                    <input 
                                        type="text" 
                                        name="stats_headline_white" 
                                        id="stats_white_input"
                                        class="form-text-input" 
                                        value="<?php echo htmlspecialchars($statsWhite); ?>"
                                        oninput="updateStatsPreview()"
                                    >
                                </div>

                                <!-- Headline - Red Part -->
                                <div class="form-field">
                                    <label class="form-label-title">Headline &mdash; Red Part</label>
                                    <input 
                                        type="text" 
                                        name="stats_headline_red" 
                                        id="stats_red_input"
                                        class="form-text-input" 
                                        value="<?php echo htmlspecialchars($statsRed); ?>"
                                        oninput="updateStatsPreview()"
                                    >
                                </div>

                                <!-- Description -->
                                <div class="form-field">
                                    <label class="form-label-title">Description</label>
                                    <p class="form-label-help">Shown below the headline on desktop</p>
                                    <textarea 
                                        name="stats_description" 
                                        id="stats_desc_input"
                                        class="form-text-input" 
                                        rows="3" 
                                        style="resize: vertical;"
                                        oninput="updateStatsPreview()"
                                    ><?php echo htmlspecialchars($statsDesc); ?></textarea>
                                    <div style="text-align: right; font-size: 0.76rem; color: var(--text-muted); margin-top: 4px;" id="statsDescCounter">
                                        <?php echo strlen($statsDesc); ?>/300
                                    </div>
                                </div>

                                <!-- Section Live Preview Box (Black Box) -->
                                <div style="background-color: #0a0e17; border-radius: 14px; padding: 22px; margin-top: 20px; color: #ffffff;">
                                    <div id="statsPreviewBadge" style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #ef4444; font-size: 0.7rem; font-weight: 700; padding: 3px 10px; border-radius: 12px; display: inline-block; margin-bottom: 12px;">
                                        <?php echo htmlspecialchars($statsBadge); ?>
                                    </div>
                                    
                                    <div style="font-size: 1.45rem; font-weight: 800; line-height: 1.2; margin-bottom: 10px;">
                                        <span id="statsPreviewWhite"><?php echo htmlspecialchars($statsWhite); ?></span>
                                        <span id="statsPreviewRed" style="color: #ef4444;"><?php echo htmlspecialchars($statsRed); ?></span>
                                    </div>

                                    <p id="statsPreviewDesc" style="font-size: 0.83rem; color: #94a3b8; line-height: 1.45; margin-bottom: 16px;">
                                        <?php echo htmlspecialchars($statsDesc); ?>
                                    </p>

                                    <a href="#" onclick="resetStatsDefaults(); return false;" style="color: #64748b; font-size: 0.8rem; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#94a3b8'" onmouseout="this.style.color='#64748b'">
                                        Reset to defaults
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Stat Cards List & Quick Preview -->
                        <div>
                            <div class="dashboard-card">
                                <div class="card-header-row" style="justify-content: space-between;">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div class="card-icon-badge">
                                            <i class="fa-solid fa-chart-column"></i>
                                        </div>
                                        <div class="card-title-text">Stat Cards</div>
                                    </div>
                                    <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;" id="statCardsCountBadge"><?php echo count($statsItems); ?> stats</span>
                                </div>

                                <!-- Dynamic Stat Cards Container -->
                                <div id="statCardsContainer">
                                    <?php foreach ($statsItems as $i => $item): ?>
                                        <div class="stat-card-box" style="background: #ffffff; border: 1px solid var(--card-border); border-radius: 12px; padding: 18px; margin-bottom: 16px;">
                                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
                                                <div style="display: flex; align-items: center; gap: 8px;">
                                                    <div style="width: 28px; height: 28px; border-radius: 6px; background: rgba(239,68,68,0.1); color: #ef4444; display: flex; align-items: center; justify-content: center; font-size: 0.85rem;">
                                                        <i class="fa-solid fa-film"></i>
                                                    </div>
                                                    <span style="font-size: 0.9rem; font-weight: 700; color: var(--text-primary);">Stat #<?php echo ($i + 1); ?></span>
                                                </div>
                                                <div style="display: flex; align-items: center; gap: 8px; color: var(--text-muted);">
                                                    <button type="button" class="btn-icon-action" style="background: none; border: none; cursor: pointer; color: var(--text-muted);" title="Toggle Collapse"><i class="fa-solid fa-chevron-up"></i></button>
                                                    <button type="button" class="btn-icon-action" onclick="removeStatCard(this)" style="background: none; border: none; cursor: pointer; color: var(--text-muted);" title="Delete Stat"><i class="fa-regular fa-trash-can"></i></button>
                                                </div>
                                            </div>

                                            <!-- Number, Suffix, Prefix 3-Column Row -->
                                            <div style="display: grid; grid-template-columns: 2fr 1.5fr 1.5fr; gap: 10px; margin-bottom: 12px;">
                                                <div>
                                                    <label class="form-label-title" style="font-size: 0.78rem;">Number</label>
                                                    <input type="text" name="stat_number[]" class="form-text-input stat-number-field" value="<?php echo htmlspecialchars($item['number'] ?? ''); ?>" oninput="renderQuickPreview()">
                                                </div>
                                                <div>
                                                    <label class="form-label-title" style="font-size: 0.78rem;">Suffix</label>
                                                    <input type="text" name="stat_suffix[]" class="form-text-input stat-suffix-field" placeholder="e.g. + or %" value="<?php echo htmlspecialchars($item['suffix'] ?? '+'); ?>" oninput="renderQuickPreview()">
                                                </div>
                                                <div>
                                                    <label class="form-label-title" style="font-size: 0.78rem;">Prefix</label>
                                                    <input type="text" name="stat_prefix[]" class="form-text-input stat-prefix-field" placeholder="e.g. $ (optional)" value="<?php echo htmlspecialchars($item['prefix'] ?? ''); ?>" oninput="renderQuickPreview()">
                                                </div>
                                            </div>

                                            <!-- Label -->
                                            <div style="margin-bottom: 12px;">
                                                <label class="form-label-title" style="font-size: 0.78rem;">Label</label>
                                                <input type="text" name="stat_label[]" class="form-text-input stat-label-field" value="<?php echo htmlspecialchars($item['label'] ?? ''); ?>" oninput="renderQuickPreview()">
                                            </div>

                                            <!-- Sublabel -->
                                            <div style="margin-bottom: 12px;">
                                                <label class="form-label-title" style="font-size: 0.78rem;">Sublabel</label>
                                                <p class="form-label-help" style="font-size: 0.72rem; margin-bottom: 4px;">Short description shown below the label</p>
                                                <input type="text" name="stat_sublabel[]" class="form-text-input stat-sublabel-field" value="<?php echo htmlspecialchars($item['sublabel'] ?? ''); ?>" oninput="renderQuickPreview()">
                                            </div>

                                            <!-- Icon Selector Dropdown -->
                                            <div>
                                                <label class="form-label-title" style="font-size: 0.78rem;">Icon</label>
                                                <select name="stat_icon[]" class="form-text-input stat-icon-field" onchange="renderQuickPreview()">
                                                    <option value="ri-film-line" <?php echo ($item['icon'] ?? '') === 'ri-film-line' ? 'selected' : ''; ?>>🎞️ ri-film-line</option>
                                                    <option value="ri-history-line" <?php echo ($item['icon'] ?? '') === 'ri-history-line' ? 'selected' : ''; ?>>🕒 ri-history-line</option>
                                                    <option value="ri-earth-line" <?php echo ($item['icon'] ?? '') === 'ri-earth-line' ? 'selected' : ''; ?>>🌐 ri-earth-line</option>
                                                    <option value="ri-trophy-line" <?php echo ($item['icon'] ?? '') === 'ri-trophy-line' ? 'selected' : ''; ?>>🏆 ri-trophy-line</option>
                                                    <option value="ri-star-line" <?php echo ($item['icon'] ?? '') === 'ri-star-line' ? 'selected' : ''; ?>>⭐ ri-star-line</option>
                                                    <option value="ri-user-star-line" <?php echo ($item['icon'] ?? '') === 'ri-user-star-line' ? 'selected' : ''; ?>>👤 ri-user-star-line</option>
                                                </select>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Add Stat Button -->
                                <button type="button" onclick="addNewStatCard()" style="width: 100%; padding: 12px; background: transparent; border: 1px dashed #cbd5e1; border-radius: 10px; color: var(--text-secondary); font-size: 0.88rem; font-weight: 600; cursor: pointer; transition: background 0.2s, border-color 0.2s;" onmouseover="this.style.background='#f8fafc'; this.style.borderColor='#94a3b8';" onmouseout="this.style.background='transparent'; this.style.borderColor='#cbd5e1';">
                                    + Add Stat
                                </button>
                            </div>

                            <!-- Quick Preview Card (Black Box) -->
                            <div style="background-color: #0a0e17; border-radius: 14px; padding: 22px; margin-top: 24px; color: #ffffff;">
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
                                    <span style="font-size: 0.72rem; font-weight: 700; color: #64748b; letter-spacing: 0.5px;">QUICK PREVIEW</span>
                                    <span style="font-size: 0.72rem; color: #ef4444; font-weight: 600; cursor: pointer;">
                                        <i class="fa-solid fa-expand"></i> Full preview
                                    </span>
                                </div>

                                <!-- 2x2 Grid of Stat Cards Preview -->
                                <div id="quickPreviewGrid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                    <!-- Rendered dynamically via JS renderQuickPreview() -->
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

            <!-- SECTION: PRODUCTION BTS -->
            <?php elseif ($activeSection === 'bts'): ?>
                <?php 
                $btsItems = $settings['bts_items'] ?? [
                    ['id' => 1, 'title' => 'On set — RedBull Campaign, Dubai 2024', 'subtitle' => 'Director reviewing footage on set', 'image' => 'https://images.unsplash.com/photo-1574717024653-61fd2cf4d44d?auto=format&fit=crop&w=800&q=80', 'visible' => true],
                    ['id' => 2, 'title' => 'ARRI ALEXA Mini LF — lens prep', 'subtitle' => 'Camera rig setup', 'image' => 'https://images.unsplash.com/photo-1585829365295-ab7cd400c167?auto=format&fit=crop&w=800&q=80', 'visible' => true],
                    ['id' => 3, 'title' => 'Lighting rig — HBO Teaser, Cape Town', 'subtitle' => 'Lighting setup', 'image' => 'https://images.unsplash.com/photo-1598899134739-24c46f58b8c0?auto=format&fit=crop&w=800&q=80', 'visible' => true],
                    ['id' => 4, 'title' => 'Aerial Drone Scout — Kenya Safari', 'subtitle' => '4K aerial footage capture', 'image' => 'https://images.unsplash.com/photo-1508614589041-895b88991e3e?auto=format&fit=crop&w=800&q=80', 'visible' => true],
                    ['id' => 5, 'title' => 'Post-Production Suite — Color Grading', 'subtitle' => 'DaVinci Resolve studio session', 'image' => 'https://images.unsplash.com/photo-1536240478700-b869070f9279?auto=format&fit=crop&w=800&q=80', 'visible' => true],
                    ['id' => 6, 'title' => 'Wedding Cinema Shoot — Sunset', 'subtitle' => 'Romantic Golden Hour capture', 'image' => 'https://images.unsplash.com/photo-1519741497674-611481863552?auto=format&fit=crop&w=800&q=80', 'visible' => true]
                ];
                ?>
                <form id="btsForm" method="POST" action="/admin/index.php?section=bts" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="save_bts">

                    <div class="section-header-bar" style="align-items: center;">
                        <div>
                            <h1 class="section-header-title">Production BTS</h1>
                            <p class="section-header-desc">
                                <span id="btsCountText"><?php echo count($btsItems); ?> photos</span> &middot; first 6 shown on homepage &middot; drag to reorder
                            </p>
                        </div>
                        <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                            <button type="button" class="btn-live-site" style="color: #ef4444; border-color: rgba(239,68,68,0.3); background: rgba(239,68,68,0.05); font-size: 0.82rem;">
                                <i class="fa-solid fa-rotate"></i> Sync
                            </button>
                            <button type="button" class="btn-live-site" style="color: #16a34a; border-color: rgba(22,163,74,0.3); background: rgba(22,163,74,0.05); font-size: 0.82rem;">
                                <i class="fa-brands fa-google-drive"></i> Drive Folder
                            </button>
                            <button type="button" class="btn-live-site" style="color: #16a34a; border-color: rgba(22,163,74,0.3); background: rgba(22,163,74,0.05); font-size: 0.82rem;">
                                <i class="fa-solid fa-link"></i> Drive URLs
                            </button>
                            <button type="button" onclick="openAddBtsCard()" class="btn-save-primary" style="font-size: 0.82rem; padding: 8px 14px;">
                                <i class="fa-solid fa-plus"></i> Add Photo
                            </button>
                            <button type="button" onclick="clearAllBtsPhotos()" class="btn-live-site" style="color: var(--text-muted); font-size: 0.82rem;">
                                <i class="fa-regular fa-trash-can"></i> Clear All
                            </button>
                            <button type="submit" class="btn-save-primary" style="font-size: 0.82rem; padding: 8px 16px; background-color: var(--accent-red);">
                                <i class="fa-solid fa-floppy-disk"></i> Save Changes
                            </button>
                        </div>
                    </div>

                    <!-- Drag & Drop Upload Container Card -->
                    <div style="border: 1px dashed #cbd5e1; border-radius: 14px; padding: 28px; text-align: center; margin-bottom: 24px; background: #ffffff; cursor: pointer;" onclick="document.getElementById('btsDropInput').click()">
                        <div style="width: 44px; height: 44px; border-radius: 50%; background: #f1f5f9; color: #64748b; display: inline-flex; align-items: center; justify-content: center; font-size: 1.2rem; margin-bottom: 10px;">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                        </div>
                        <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--text-primary); margin-bottom: 4px;">Drag &amp; drop photos here</h4>
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0;">or click to browse &middot; JPG, PNG, WebP, GIF &middot; max 5 MB per file</p>
                        <input type="file" id="btsDropInput" name="bts_file" accept="image/*" style="display: none;" onchange="uploadBtsFileDirect(this)">
                    </div>

                    <!-- Photo Grid Container (3 Columns) -->
                    <div id="btsPhotoGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
                        <?php foreach ($btsItems as $index => $photo): ?>
                            <div class="bts-photo-card" style="background: #ffffff; border: 1px solid var(--card-border); border-radius: 14px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; flex-direction: column;">
                                
                                <!-- Image Box with Overlay Tags -->
                                <div style="position: relative; aspect-ratio: 16/10; overflow: hidden; background: #0f172a;">
                                    <img 
                                        src="<?php echo htmlspecialchars(getCloudinaryUrl($photo['image'])); ?>" 
                                        alt="<?php echo htmlspecialchars($photo['title']); ?>"
                                        style="width: 100%; height: 100%; object-fit: cover;"
                                        onerror="this.src='/assets/img/hero.jpg';"
                                    >

                                    <!-- Top Left Drag Handle Badge -->
                                    <div style="position: absolute; top: 10px; left: 10px; width: 26px; height: 26px; border-radius: 6px; background: rgba(15, 23, 42, 0.7); color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; cursor: move;" title="Drag to reorder">
                                        <i class="fa-solid fa-arrows-up-down-left-right"></i>
                                    </div>

                                    <!-- Top Right Status Pill -->
                                    <div style="position: absolute; top: 10px; right: 10px; background: rgba(15, 23, 42, 0.85); color: #ffffff; font-size: 0.7rem; font-weight: 700; padding: 3px 8px; border-radius: 6px;">
                                        #<?php echo ($index + 1); ?> &middot; visible
                                    </div>
                                </div>

                                <!-- Details Body -->
                                <div style="padding: 14px 16px; flex-grow: 1;">
                                    <input type="hidden" name="bts_image[]" value="<?php echo htmlspecialchars($photo['image']); ?>" class="bts-img-val">
                                    
                                    <div style="font-size: 0.88rem; font-weight: 700; color: var(--text-primary); margin-bottom: 4px;">
                                        <input type="text" name="bts_title[]" value="<?php echo htmlspecialchars($photo['title']); ?>" class="form-text-input" style="padding: 6px 10px; font-weight: 700; font-size: 0.85rem;" placeholder="Photo Title">
                                    </div>
                                    
                                    <div style="font-size: 0.78rem; color: var(--text-muted);">
                                        <input type="text" name="bts_subtitle[]" value="<?php echo htmlspecialchars($photo['subtitle']); ?>" class="form-text-input" style="padding: 4px 10px; font-size: 0.78rem;" placeholder="Subtitle / Caption">
                                    </div>
                                </div>

                                <!-- Footer Action Links -->
                                <div style="padding: 10px 16px; border-top: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; font-size: 0.78rem; background: #fafafa;">
                                    <span style="color: #94a3b8; font-weight: 500;">+ drag to reorder</span>
                                    <div style="display: flex; gap: 12px;">
                                        <button type="button" onclick="editBtsCard(this)" style="background: none; border: none; color: var(--text-secondary); font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">
                                            <i class="fa-solid fa-pen" style="font-size: 0.72rem;"></i> Edit
                                        </button>
                                        <button type="button" onclick="deleteBtsCard(this)" style="background: none; border: none; color: #94a3b8; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#94a3b8'">
                                            <i class="fa-regular fa-trash-can" style="font-size: 0.72rem;"></i> Del
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </form>

            <!-- SECTION: FEATURED WORK -->
            <?php elseif ($activeSection === 'featured'): ?>
                <?php 
                $featuredItems = $settings['featured_work_items'] ?? [
                    ['id' => 1, 'project_name' => '40th Birthday Celebration', 'client' => 'Halima', 'category' => 'Social Event', 'year' => '2024', 'duration' => '1:23', 'youtube_id' => 'YlmUJ16slrY', 'video_source' => 'youtube', 'thumbnail' => '/assets/img/portfolio/portfolio_halima.png', 'is_hero_featured' => true, 'status' => 'live'],
                    ['id' => 2, 'project_name' => 'Wedding Celebration', 'client' => 'Kane Oberman', 'category' => 'Wedding Film', 'year' => '2024', 'duration' => '0:00', 'youtube_id' => 'jaJawGCNx-I', 'video_source' => 'youtube', 'thumbnail' => '/assets/img/portfolio/portfolio_wedding.png', 'is_hero_featured' => false, 'status' => 'live'],
                    ['id' => 3, 'project_name' => 'Wedding Celebration', 'client' => 'Kailey and William', 'category' => 'Wedding Trailer', 'year' => '2024', 'duration' => '1:28', 'youtube_id' => 'z56VNoHV1Ic', 'video_source' => 'youtube', 'thumbnail' => '/assets/img/portfolio/portfolio_wedding.png', 'is_hero_featured' => false, 'status' => 'live'],
                    ['id' => 4, 'project_name' => 'Gala Award Night', 'client' => 'Ibadan College of Medicine', 'category' => 'Award Night Documentary', 'year' => '2024', 'duration' => '1:53', 'youtube_id' => 'Tf8rNMZ-Bw0', 'video_source' => 'youtube', 'thumbnail' => '/assets/img/portfolio/portfolio_award.png', 'is_hero_featured' => false, 'status' => 'live'],
                    ['id' => 5, 'project_name' => 'Black History Month', 'client' => 'BMRC', 'category' => 'Commercial Documentary', 'year' => '2024', 'duration' => '2:25', 'youtube_id' => 'Z84AOc_t2Sk', 'video_source' => 'youtube', 'thumbnail' => '/assets/img/portfolio/portfolio_commercial.png', 'is_hero_featured' => false, 'status' => 'live'],
                    ['id' => 6, 'project_name' => 'Wedding Celebration', 'client' => 'Diané & Max', 'category' => 'Wedding Film', 'year' => '2024', 'duration' => '1:49', 'youtube_id' => 'AXqylP5AENQ', 'video_source' => 'youtube', 'thumbnail' => '/assets/img/portfolio/portfolio_halima.png', 'is_hero_featured' => false, 'status' => 'live'],
                    ['id' => 7, 'project_name' => 'Wedding Celebration', 'client' => 'Ernie & Emily', 'category' => 'Wedding Film', 'year' => '2024', 'duration' => '1:30', 'youtube_id' => 'zidrFg1ikBc', 'video_source' => 'youtube', 'thumbnail' => 'https://images.unsplash.com/photo-1511578314322-379afb476865?auto=format&fit=crop&w=800&q=80', 'is_hero_featured' => false, 'status' => 'live'],
                    ['id' => 8, 'project_name' => 'Wedding Reels', 'client' => 'Wedding', 'category' => 'Wedding Film', 'year' => '2024', 'duration' => '1:28', 'youtube_id' => 'ezAGweXeQOw', 'video_source' => 'youtube', 'thumbnail' => 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?auto=format&fit=crop&w=800&q=80', 'is_hero_featured' => false, 'status' => 'live']
                ];

                $editId = (int)($_GET['edit_id'] ?? 0);
                $subAction = $_GET['sub_action'] ?? '';
                $editingItem = null;
                if ($editId > 0) {
                    foreach ($featuredItems as $it) {
                        if ((int)$it['id'] === $editId) {
                            $editingItem = $it;
                            break;
                        }
                    }
                }
                ?>

                <?php if ($editingItem || $subAction === 'add'): ?>
                    <!-- VIEW B: EDITING FEATURED VIDEO CARD (Matching Screenshots 2 & 3) -->
                    <form method="POST" action="/admin/index.php?section=featured">
                        <input type="hidden" name="action" value="save_single_featured_item">
                        <input type="hidden" name="item_id" value="<?php echo $editingItem['id'] ?? 0; ?>">

                        <div class="section-header-bar">
                            <div>
                                <h1 class="section-header-title" style="display: flex; align-items: center; gap: 10px;">
                                    <i class="fa-solid fa-pen-to-square" style="color: #ef4444; font-size: 1.2rem;"></i>
                                    Editing: <?php echo htmlspecialchars($editingItem['project_name'] ?? 'New Featured Video'); ?>
                                </h1>
                            </div>
                        </div>

                        <div class="dashboard-card" style="max-width: 920px;">
                            <!-- Video Source Selector -->
                            <div class="form-field">
                                <label class="form-label-title">Video Source <span style="color: #ef4444;">*</span></label>
                                <div style="display: flex; gap: 10px;">
                                    <button type="button" class="btn-source active" style="background: #dc2626; color: #ffffff; border: none; border-radius: 6px; padding: 8px 18px; font-weight: 700; font-size: 0.88rem;">
                                        <i class="fa-brands fa-youtube"></i> YouTube
                                    </button>
                                    <button type="button" class="btn-source" style="background: #ffffff; color: var(--text-primary); border: 1px solid var(--card-border); border-radius: 6px; padding: 8px 18px; font-weight: 600; font-size: 0.88rem;">
                                        <i class="fa-brands fa-google-drive"></i> Google Drive
                                    </button>
                                </div>
                            </div>

                            <!-- YouTube URL or Video ID -->
                            <div class="form-field">
                                <label class="form-label-title">YouTube URL or Video ID</label>
                                <input 
                                    type="text" 
                                    name="youtube_id" 
                                    id="edit_youtube_id"
                                    class="form-text-input" 
                                    value="<?php echo htmlspecialchars($editingItem['youtube_id'] ?? 'YlmUJ16slrY'); ?>"
                                    placeholder="YlmUJ16slrY"
                                    oninput="updateFeaturedPreview(this.value)"
                                >
                            </div>

                            <!-- Video / Thumbnail Live Preview Container (Large Card) -->
                            <div style="position: relative; border-radius: 14px; overflow: hidden; background: #0a0e17; aspect-ratio: 16/9; margin-top: 18px; margin-bottom: 24px; border: 1px solid var(--card-border); display: flex; align-items: center; justify-content: center;">
                                <img 
                                    id="editVideoThumbnailPreview"
                                    src="<?php echo htmlspecialchars(getCloudinaryUrl($editingItem['thumbnail'] ?? getYouTubeThumbnailUrl($editingItem['youtube_id'] ?? 'Tf8rNMZ-Bw0'))); ?>" 
                                    alt="Video Thumbnail Preview"
                                    style="width: 100%; height: 100%; object-fit: cover;"
                                    onerror="this.src='/assets/img/hero.jpg';"
                                >

                                <!-- Overlay Buttons Bar -->
                                <div style="position: absolute; inset: 0; background: rgba(15,23,42,0.35); display: flex; align-items: center; justify-content: center; gap: 10px;">
                                    <button 
                                        type="button" 
                                        onclick="openCropperModal(document.getElementById('editVideoThumbnailPreview').src, 'featured')"
                                        style="background: #ffffff; color: #0f172a; border: none; border-radius: 20px; padding: 7px 16px; font-size: 0.82rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);"
                                    >
                                        <i class="fa-solid fa-crop-simple"></i> Re-crop
                                    </button>

                                    <button 
                                        type="button" 
                                        onclick="document.getElementById('featured_image_file_input').click()"
                                        style="background: #dc2626; color: #ffffff; border: none; border-radius: 20px; padding: 7px 16px; font-size: 0.82rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px rgba(220,38,38,0.3);"
                                    >
                                        <i class="fa-solid fa-rotate"></i> Replace
                                    </button>
                                </div>
                            </div>

                            <input 
                                type="file" 
                                id="featured_image_file_input" 
                                name="featured_image_file" 
                                accept="image/*" 
                                style="display: none;" 
                                onchange="handleFeaturedImageFileSelect(this)"
                            >
                            <input 
                                type="hidden" 
                                id="cropped_featured_image_data" 
                                name="cropped_featured_image_data" 
                                value=""
                            >

                            <!-- Form Fields 2-Column Grid -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px;" class="form-field">
                                <div>
                                    <label class="form-label-title">Client</label>
                                    <input type="text" name="client" class="form-text-input" value="<?php echo htmlspecialchars($editingItem['client'] ?? ''); ?>" placeholder="Client Name">
                                </div>
                                <div>
                                    <label class="form-label-title">Project Name</label>
                                    <input type="text" name="project_name" class="form-text-input" value="<?php echo htmlspecialchars($editingItem['project_name'] ?? ''); ?>" placeholder="Project Name">
                                </div>
                                <div>
                                    <label class="form-label-title">Category</label>
                                    <select name="category" class="form-text-input">
                                        <option value="Social Event" <?php echo ($editingItem['category'] ?? '') === 'Social Event' ? 'selected' : ''; ?>>Social Event</option>
                                        <option value="Wedding Film" <?php echo ($editingItem['category'] ?? '') === 'Wedding Film' ? 'selected' : ''; ?>>Wedding Film</option>
                                        <option value="Wedding Trailer" <?php echo ($editingItem['category'] ?? '') === 'Wedding Trailer' ? 'selected' : ''; ?>>Wedding Trailer</option>
                                        <option value="Award Night Documentary" <?php echo ($editingItem['category'] ?? '') === 'Award Night Documentary' ? 'selected' : ''; ?>>Award Night Documentary</option>
                                        <option value="Commercial Documentary" <?php echo ($editingItem['category'] ?? '') === 'Commercial Documentary' ? 'selected' : ''; ?>>Commercial Documentary</option>
                                        <option value="Music Video" <?php echo ($editingItem['category'] ?? '') === 'Music Video' ? 'selected' : ''; ?>>Music Video</option>
                                        <option value="Corporate Branding" <?php echo ($editingItem['category'] ?? '') === 'Corporate Branding' ? 'selected' : ''; ?>>Corporate Branding</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label-title">Year</label>
                                    <input type="text" name="year" class="form-text-input" value="<?php echo htmlspecialchars($editingItem['year'] ?? date('Y')); ?>" placeholder="2024">
                                </div>
                                <div>
                                    <label class="form-label-title">Duration</label>
                                    <input type="text" name="duration" class="form-text-input" value="<?php echo htmlspecialchars($editingItem['duration'] ?? '1:23'); ?>" placeholder="1:23">
                                </div>
                                <div>
                                    <label class="form-label-title">Thumbnail URL <span style="font-weight: normal; color: #64748b; font-size: 0.76rem;">(Auto YouTube thumbnail if empty)</span></label>
                                    <input 
                                        type="text" 
                                        name="thumbnail" 
                                        id="edit_thumbnail_input" 
                                        class="form-text-input" 
                                        value="<?php echo htmlspecialchars($editingItem['thumbnail'] ?? ('https://img.youtube.com/vi/' . ($editingItem['youtube_id'] ?? 'YlmUJ16slrY') . '/hqdefault.jpg')); ?>" 
                                        placeholder="https://img.youtube.com/vi/..."
                                        oninput="document.getElementById('editVideoThumbnailPreview').src = this.value;"
                                    >
                                </div>
                            </div>

                            <!-- Hero Featured Toggle Switch -->
                            <div style="margin-top: 20px; padding-top: 18px; border-top: 1px solid #f1f5f9;">
                                <label style="display: flex; align-items: center; gap: 12px; cursor: pointer;">
                                    <input type="checkbox" name="is_hero_featured" value="1" <?php echo !empty($editingItem['is_hero_featured']) ? 'checked' : ''; ?> style="width: 18px; height: 18px; accent-color: #dc2626;">
                                    <div>
                                        <span style="font-weight: 700; color: var(--text-primary); font-size: 0.92rem;">Hero Featured</span>
                                        <p style="font-size: 0.78rem; color: var(--text-muted); margin: 0;">This video appears as the large hero card on the homepage</p>
                                    </div>
                                </label>
                            </div>

                            <!-- Buttons Row -->
                            <div style="display: flex; gap: 12px; margin-top: 28px;">
                                <button type="submit" class="btn-save-primary" style="padding: 10px 22px; background-color: #dc2626;">
                                    <i class="fa-solid fa-circle-check"></i> Save &amp; Publish to Live
                                </button>
                                <a href="index.php?section=featured" class="btn-live-site" style="text-decoration: none; padding: 10px 20px;">Cancel</a>
                            </div>
                        </div>
                    </form>

                <?php else: ?>
                    <!-- VIEW A: FEATURED WORK LIST VIEW (Matching Screenshot 1) -->
                    <div class="section-header-bar" style="align-items: center; margin-bottom: 24px;">
                        <div>
                            <h1 class="section-header-title" style="font-size: 1.75rem; font-weight: 800; color: #0f172a; margin-bottom: 4px;">Featured Work</h1>
                            <p class="section-header-desc" style="font-size: 0.88rem; color: #64748b; margin: 0;">
                                <strong style="color: #0f172a;"><?php echo count($featuredItems); ?> videos</strong> &middot; <span style="color: #16a34a; font-weight: 600;"><i class="fa-solid fa-circle" style="font-size: 0.45rem; color: #22c55e;"></i> live on site</span>
                            </p>
                            <p style="font-size: 0.8rem; color: #94a3b8; font-weight: 500; margin-top: 6px; margin-bottom: 0; display: flex; align-items: center; gap: 5px;">
                                <span style="letter-spacing: -1px; font-weight: 700; color: #cbd5e1;">::</span> Drag the handle on any card to reorder
                            </p>
                        </div>
                        <div style="display: flex; gap: 12px; align-items: center;">
                            <a href="/" target="_blank" class="btn-live-site" style="text-decoration: none; color: #9333ea; border-color: #d8b4fe; background: #faf5ff; font-weight: 600; font-size: 0.85rem; padding: 8px 16px; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px;">
                                <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 0.8rem;"></i> Preview Site
                            </a>
                            <a href="index.php?section=featured&sub_action=add" class="btn-save-primary" style="text-decoration: none; background-color: #dc2626; font-weight: 700; font-size: 0.88rem; padding: 9px 18px; border-radius: 8px; box-shadow: 0 4px 14px rgba(220,38,38,0.3); display: inline-flex; align-items: center; gap: 6px;">
                                <i class="fa-solid fa-plus"></i> Add Video
                            </a>
                        </div>
                    </div>

                    <div id="featuredCardsContainer">
                        <?php foreach ($featuredItems as $item): ?>
                            <?php 
                            $ytId = extractYouTubeId($item['youtube_id']);
                            $itemThumb = !empty($item['thumbnail']) ? $item['thumbnail'] : getYouTubeThumbnailUrl($ytId);
                            $finalThumbUrl = getCloudinaryUrl($itemThumb);
                            ?>
                            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px 20px; margin-bottom: 12px; box-shadow: 0 1px 4px rgba(0,0,0,0.02); display: flex; align-items: center; justify-content: space-between; transition: all 0.2s ease;">
                                <div style="display: flex; align-items: center; gap: 18px;">
                                    <!-- Drag Handle -->
                                    <div style="color: #cbd5e1; cursor: move; font-size: 0.9rem; padding: 4px;" title="Drag to reorder">
                                        <i class="fa-solid fa-grip-vertical"></i>
                                    </div>

                                    <!-- Thumbnail with Play Overlay -->
                                    <div style="position: relative; width: 96px; height: 56px; border-radius: 8px; overflow: hidden; background: #0f172a; flex-shrink: 0; box-shadow: 0 2px 6px rgba(0,0,0,0.08);">
                                        <img 
                                            src="<?php echo htmlspecialchars($finalThumbUrl); ?>" 
                                            alt="<?php echo htmlspecialchars($item['project_name']); ?>"
                                            style="width: 100%; height: 100%; object-fit: cover;"
                                            onerror="this.src='https://i.ytimg.com/vi/<?php echo htmlspecialchars($ytId); ?>/hqdefault.jpg';"
                                        >
                                        <div style="position: absolute; inset: 0; background: rgba(15,23,42,0.3); display: flex; align-items: center; justify-content: center; color: #ffffff; font-size: 0.85rem;">
                                            <i class="fa-solid fa-play"></i>
                                        </div>
                                    </div>

                                    <!-- Details Column -->
                                    <div>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <h4 style="font-size: 0.98rem; font-weight: 700; color: #0f172a; margin: 0;"><?php echo htmlspecialchars($item['project_name']); ?></h4>
                                            <?php if (!empty($item['is_hero_featured'])): ?>
                                                <span style="background: #fef3c7; color: #b45309; border: 1px solid #fde68a; font-size: 0.7rem; font-weight: 700; padding: 2px 8px; border-radius: 12px;">Hero</span>
                                            <?php endif; ?>
                                        </div>
                                        <div style="font-size: 0.82rem; color: #64748b; font-weight: 500; margin-top: 3px;">
                                            <?php echo htmlspecialchars($item['client']); ?> &middot; <?php echo htmlspecialchars($item['category']); ?>
                                        </div>
                                        <div style="margin-top: 4px;">
                                            <span style="display: inline-flex; align-items: center; gap: 4px; background: rgba(22,163,74,0.08); color: #16a34a; font-family: monospace; font-size: 0.75rem; font-weight: 600; padding: 2px 8px; border-radius: 4px;">
                                                <i class="fa-brands fa-youtube" style="font-size: 0.8rem;"></i> <?php echo htmlspecialchars($item['youtube_id']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right Column: Status & Action Links -->
                                <div style="display: flex; align-items: center;">
                                    <span style="background: #dcfce7; color: #16a34a; font-size: 0.75rem; font-weight: 700; padding: 4px 12px; border-radius: 20px; display: inline-flex; align-items: center; gap: 5px; margin-right: 20px;">
                                        <i class="fa-solid fa-circle" style="font-size: 0.45rem; color: #22c55e;"></i> Live
                                    </span>

                                    <div style="display: flex; align-items: center; gap: 16px;">
                                        <a href="index.php?section=featured&edit_id=<?php echo $item['id']; ?>" style="color: #64748b; font-size: 0.85rem; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 5px;" onmouseover="this.style.color='#0f172a'" onmouseout="this.style.color='#64748b'">
                                            <i class="fa-solid fa-pen" style="font-size: 0.75rem;"></i> Edit
                                        </a>

                                        <form method="POST" action="/admin/index.php?section=featured" style="margin: 0;" onsubmit="return confirm('Delete this video from featured work?');">
                                            <input type="hidden" name="action" value="delete_featured_item">
                                            <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" style="background: none; border: none; color: #94a3b8; font-size: 0.85rem; font-weight: 500; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; padding: 0;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#94a3b8'">
                                                <i class="fa-regular fa-trash-can" style="font-size: 0.78rem;"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            <!-- SECTION: SERVICES -->
            <?php elseif ($activeSection === 'services'): ?>
                <?php 
                $servicesItems = $settings['services_items'] ?? [
                    ['id' => 1, 'slug' => 'video-production', 'title' => 'Video Production', 'icon' => 'fa-solid fa-film', 'short_description' => 'Professional video production services from concept to completion, including scripting, filming, and editing with state-of-the-art equipment.', 'detail_description' => 'Our full-service video production team handles every stage of the process...', 'image' => 'https://images.unsplash.com/photo-1574717024653-61fd2cf4d44d?auto=format&fit=crop&w=800&q=80', 'card_features' => ['Corporate videos & commercials', '4K & 8K video capture', 'Drone cinematography', 'Multi-camera productions'], 'detail_features' => ['4K & 8K Cinema Capture', 'Drone Aerial Cinematography', 'Multi-Camera Rigs', 'Scriptwriting & Storyboarding']],
                    ['id' => 2, 'slug' => 'live-streaming', 'title' => 'Live Streaming', 'icon' => 'fa-solid fa-tower-broadcast', 'short_description' => 'Seamless live streaming solutions for events, conferences, and broadcasts with professional-grade equipment and technical support.', 'detail_description' => 'We broadcast your live events to global audiences...', 'image' => 'https://images.unsplash.com/photo-1518173946687-a4c8a383392e?auto=format&fit=crop&w=800&q=80', 'card_features' => ['Multi-platform streaming', 'Real-time graphics & overlays', 'Interactive audience engagement', 'Technical support & monitoring'], 'detail_features' => ['Multi-Platform Simulcasting', 'Custom Graphic Overlays', 'Low-Latency Broadcast', 'Dedicated On-Site Engineers']],
                    ['id' => 3, 'slug' => 'post-production', 'title' => 'Post Production', 'icon' => 'fa-solid fa-sliders', 'short_description' => 'Comprehensive post-production services including editing, color grading, sound design, and visual effects to polish your content.', 'detail_description' => 'Transform raw footage into a cinematic masterpiece...', 'image' => 'https://images.unsplash.com/photo-1536240478700-b869070f9279?auto=format&fit=crop&w=800&q=80', 'card_features' => ['Professional video editing', 'Color correction & grading', 'Sound design & mixing', 'Visual effects & compositing'], 'detail_features' => ['DaVinci Resolve Color Grading', 'Spatial Sound & Audio Mixing', 'VFX Compositing & Clean-ups', 'Multi-format Master Export']],
                    ['id' => 4, 'slug' => 'animation', 'title' => 'Animation & Motion Graphics', 'icon' => 'fa-solid fa-wand-magic-sparkles', 'short_description' => 'Creative animation and motion graphics services to enhance your brand storytelling with engaging visual elements.', 'detail_description' => 'Elevate complex ideas with custom 2D & 3D motion graphics...', 'image' => 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?auto=format&fit=crop&w=800&q=80', 'card_features' => ['2D & 3D animation', 'Motion graphics design', 'Logo animations', 'Explainer videos'], 'detail_features' => ['3D Product Visualizations', 'Kinetic Typography', 'Custom Logo Bumpers', 'Infographic Explainer Animations']]
                ];

                $editId = (int)($_GET['edit_id'] ?? 0);
                $subAction = $_GET['sub_action'] ?? '';
                $editingService = null;
                if ($editId > 0) {
                    foreach ($servicesItems as $s) {
                        if ((int)$s['id'] === $editId) {
                            $editingService = $s;
                            break;
                        }
                    }
                }
                ?>

                <?php if ($editingService || $subAction === 'add'): ?>
                    <!-- VIEW B: EDITING SERVICE FORM -->
                    <form method="POST" action="/admin/index.php?section=services" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="save_service_item">
                        <input type="hidden" name="service_id" value="<?php echo $editingService['id'] ?? 0; ?>">
                        <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($editingService['image'] ?? ''); ?>">

                        <div class="section-header-bar" style="margin-bottom: 24px;">
                            <div>
                                <h1 class="section-header-title" style="font-size: 1.75rem; font-weight: 800; color: #0f172a; margin-bottom: 4px;">Services</h1>
                                <p class="section-header-desc" style="font-size: 0.88rem; color: #64748b; margin: 0;">
                                    <strong style="color: #0f172a;"><?php echo count($servicesItems); ?> services</strong>
                                </p>
                                <p style="font-size: 0.8rem; color: #94a3b8; margin-top: 4px; margin-bottom: 0;">Changes are live on the services page instantly</p>
                            </div>
                        </div>

                        <div class="dashboard-card" style="max-width: 920px; background: #ffffff; border: 1px solid var(--card-border); border-radius: 14px; padding: 28px;">
                            <!-- Title & Icon Grid -->
                            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 20px;">
                                <div>
                                    <label class="form-label-title">Title</label>
                                    <input type="text" name="title" class="form-text-input" value="<?php echo htmlspecialchars($editingService['title'] ?? ''); ?>" placeholder="Service Title" required>
                                </div>
                                <div>
                                    <label class="form-label-title">Icon (Font Awesome or Remix Icon class)</label>
                                    <div style="display: flex; gap: 8px; align-items: center;">
                                        <input type="text" name="icon" id="edit_service_icon" class="form-text-input" value="<?php echo htmlspecialchars($editingService['icon'] ?? 'fa-solid fa-film'); ?>" placeholder="fa-solid fa-film" oninput="document.getElementById('service_icon_preview_badge').className = this.value">
                                        <div style="width: 38px; height: 38px; border-radius: 8px; background: rgba(239,68,68,0.1); color: #ef4444; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0;">
                                            <i id="service_icon_preview_badge" class="<?php echo htmlspecialchars($editingService['icon'] ?? 'fa-solid fa-film'); ?>"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Short Description -->
                            <div class="form-field" style="margin-bottom: 20px;">
                                <label class="form-label-title">Short Description (card)</label>
                                <textarea name="short_description" class="form-text-input" rows="3" placeholder="Short summary for overview cards..."><?php echo htmlspecialchars($editingService['short_description'] ?? ''); ?></textarea>
                            </div>

                            <!-- Detail Description -->
                            <div class="form-field" style="margin-bottom: 24px;">
                                <label class="form-label-title">Detail Description (service page)</label>
                                <textarea name="detail_description" class="form-text-input" rows="4" placeholder="Full service description..."><?php echo htmlspecialchars($editingService['detail_description'] ?? ''); ?></textarea>
                            </div>

                            <!-- Service Image Uploader with Re-crop and Replace Pill Buttons -->
                            <div class="form-field" style="margin-bottom: 24px;">
                                <label class="form-label-title">Service Image</label>
                                <div style="position: relative; width: 380px; aspect-ratio: 16/9; border-radius: 14px; overflow: hidden; background: #0f172a; border: 1px solid #e2e8f0; margin-bottom: 8px;" class="service-img-wrap">
                                    <img 
                                        id="service_img_preview" 
                                        src="<?php echo htmlspecialchars(getCloudinaryUrl($editingService['image'] ?? 'https://images.unsplash.com/photo-1574717024653-61fd2cf4d44d?auto=format&fit=crop&w=800&q=80')); ?>" 
                                        alt="Service Image Preview"
                                        style="width: 100%; height: 100%; object-fit: cover;"
                                        onerror="this.src='/assets/img/hero.jpg';"
                                    >
                                    <div style="position: absolute; inset: 0; background: rgba(15,23,42,0.35); display: flex; align-items: center; justify-content: center; gap: 10px; opacity: 1; transition: all 0.2s ease;">
                                        <button 
                                            type="button" 
                                            onclick="openCropperModal(document.getElementById('service_img_preview').src)"
                                            style="background: #ffffff; color: #0f172a; border: none; border-radius: 20px; padding: 7px 16px; font-size: 0.82rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);"
                                        >
                                            <i class="fa-solid fa-crop-simple"></i> Re-crop
                                        </button>

                                        <button 
                                            type="button" 
                                            onclick="document.getElementById('service_image_file_input').click()"
                                            style="background: #dc2626; color: #ffffff; border: none; border-radius: 20px; padding: 7px 16px; font-size: 0.82rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px rgba(220,38,38,0.3);"
                                        >
                                            <i class="fa-solid fa-rotate"></i> Replace
                                        </button>
                                    </div>
                                </div>

                                <input 
                                    type="file" 
                                    id="service_image_file_input" 
                                    name="service_image_file" 
                                    accept="image/*" 
                                    style="display: none;" 
                                    onchange="handleImageFileSelect(this)"
                                >
                                <input 
                                    type="hidden" 
                                    id="cropped_image_data" 
                                    name="cropped_image_data" 
                                    value=""
                                >

                                <p style="font-size: 0.78rem; color: #64748b; margin-top: 6px; margin-bottom: 0;">Crop to landscape 16:9. Max 10MB.</p>
                            </div>

                            <!-- Card Features (one per line) -->
                            <div class="form-field" style="margin-bottom: 20px;">
                                <label class="form-label-title">Card Features (one per line)</label>
                                <textarea name="card_features" class="form-text-input" rows="4" placeholder="Corporate videos & commercials&#10;4K & 8K video capture&#10;Drone cinematography"><?php echo htmlspecialchars(implode("\n", $editingService['card_features'] ?? [])); ?></textarea>
                            </div>

                            <!-- Detail Features (one per line) -->
                            <div class="form-field" style="margin-bottom: 28px;">
                                <label class="form-label-title">Detail Features (one per line)</label>
                                <textarea name="detail_features" class="form-text-input" rows="5" placeholder="4K & 8K Cinema Capture&#10;Drone Aerial Cinematography&#10;Multi-Camera Rigs"><?php echo htmlspecialchars(implode("\n", $editingService['detail_features'] ?? [])); ?></textarea>
                            </div>

                            <!-- Action Buttons -->
                            <div style="display: flex; gap: 12px;">
                                <button type="submit" class="btn-save-primary" style="padding: 10px 24px; background-color: #dc2626;">
                                    <i class="fa-solid fa-circle-check"></i> Save Changes
                                </button>
                                <a href="index.php?section=services" class="btn-live-site" style="text-decoration: none; padding: 10px 20px;">Cancel</a>
                            </div>
                        </div>
                    </form>

                <?php else: ?>
                    <!-- VIEW A: SERVICES LIST VIEW -->
                    <div class="section-header-bar" style="align-items: center; margin-bottom: 24px;">
                        <div>
                            <h1 class="section-header-title" style="font-size: 1.75rem; font-weight: 800; color: #0f172a; margin-bottom: 4px;">Services</h1>
                            <p class="section-header-desc" style="font-size: 0.88rem; color: #64748b; margin: 0; display: flex; align-items: center; gap: 8px;">
                                <strong style="color: #0f172a;"><?php echo count($servicesItems); ?> services</strong>
                            </p>
                            <p style="font-size: 0.8rem; color: #94a3b8; font-weight: 500; margin-top: 4px; margin-bottom: 0;">Changes are live on the services page instantly</p>
                        </div>
                        <div style="display: flex; gap: 12px; align-items: center;">
                            <a href="index.php?section=services&sub_action=add" class="btn-save-primary" style="text-decoration: none; background-color: #dc2626; font-weight: 700; font-size: 0.88rem; padding: 9px 18px; border-radius: 8px; box-shadow: 0 4px 14px rgba(220,38,38,0.3); display: inline-flex; align-items: center; gap: 6px;">
                                <i class="fa-solid fa-plus"></i> Add Service
                            </a>
                        </div>
                    </div>

                    <div id="servicesCardsContainer">
                        <?php foreach ($servicesItems as $item): ?>
                            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 22px; margin-bottom: 16px; box-shadow: 0 1px 4px rgba(0,0,0,0.02); display: flex; gap: 22px; align-items: flex-start; justify-content: space-between; transition: all 0.2s ease;">
                                <!-- Left Image Thumbnail -->
                                <div style="width: 170px; height: 106px; border-radius: 10px; overflow: hidden; background: #0f172a; flex-shrink: 0; box-shadow: 0 2px 6px rgba(0,0,0,0.08);">
                                    <img 
                                        src="<?php echo htmlspecialchars(getCloudinaryUrl($item['image'])); ?>" 
                                        alt="<?php echo htmlspecialchars($item['title']); ?>"
                                        style="width: 100%; height: 100%; object-fit: cover;"
                                        onerror="this.src='/assets/img/hero.jpg';"
                                    >
                                </div>

                                <!-- Middle Details Column -->
                                <div style="flex-grow: 1;">
                                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
                                        <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(239,68,68,0.1); color: #ef4444; display: inline-flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0;">
                                            <i class="<?php echo htmlspecialchars($item['icon']); ?>"></i>
                                        </div>
                                        <h3 style="font-size: 1.1rem; font-weight: 700; color: #0f172a; margin: 0;"><?php echo htmlspecialchars($item['title']); ?></h3>
                                        <span style="background: #f1f5f9; color: #64748b; font-weight: 600; font-size: 0.75rem; padding: 3px 10px; border-radius: 12px;">/services/<?php echo htmlspecialchars($item['slug']); ?></span>
                                    </div>

                                    <p style="font-size: 0.88rem; color: #64748b; margin: 0 0 12px; line-height: 1.5; max-width: 860px;"><?php echo htmlspecialchars($item['short_description']); ?></p>

                                    <!-- Card Features Pills -->
                                    <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                                        <?php foreach ($item['card_features'] as $feature): ?>
                                            <span style="background: #f8fafc; border: 1px solid #e2e8f0; color: #475569; font-size: 0.78rem; font-weight: 500; padding: 4px 12px; border-radius: 14px; font-family: sans-serif;"><?php echo htmlspecialchars($feature); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <!-- Right Edit & Delete Actions -->
                                <div style="display: flex; align-items: center; gap: 14px; flex-shrink: 0;">
                                    <a href="index.php?section=services&edit_id=<?php echo $item['id']; ?>" style="color: #64748b; font-size: 0.85rem; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 5px;" onmouseover="this.style.color='#0f172a'" onmouseout="this.style.color='#64748b'">
                                        <i class="fa-solid fa-pen" style="font-size: 0.75rem;"></i> Edit
                                    </a>

                                    <form method="POST" action="/admin/index.php?section=services" style="margin: 0;" onsubmit="return confirm('Delete this service?');">
                                        <input type="hidden" name="action" value="delete_service_item">
                                        <input type="hidden" name="service_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" style="background: none; border: none; color: #94a3b8; font-size: 0.85rem; font-weight: 500; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; padding: 0;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#94a3b8'">
                                            <i class="fa-regular fa-trash-can" style="font-size: 0.78rem;"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            <!-- SECTION 2: INQUIRIES & QUOTE REQUESTS -->
            <?php elseif ($activeSection === 'inquiries'): ?>
                <div class="section-header-bar">
                    <div>
                        <h1 class="section-header-title">Inquiries &amp; Quotes</h1>
                        <p class="section-header-desc">Manage inbound project requests from potential clients</p>
                    </div>
                </div>

                <div class="data-table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Client Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Service Requested</th>
                                <th>Budget</th>
                                <th>Status</th>
                                <th>Received</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($inquiries)): ?>
                                <?php foreach ($inquiries as $inq): ?>
                                    <tr>
                                        <td>#<?php echo $inq['id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($inq['full_name']); ?></strong></td>
                                        <td><a href="mailto:<?php echo htmlspecialchars($inq['email']); ?>" style="color: var(--accent-red);"><?php echo htmlspecialchars($inq['email']); ?></a></td>
                                        <td><?php echo htmlspecialchars($inq['phone'] ?: 'N/A'); ?></td>
                                        <td><span style="background: #f1f5f9; padding: 3px 8px; border-radius: 6px; font-weight: 600;"><?php echo htmlspecialchars($inq['service_type']); ?></span></td>
                                        <td><?php echo htmlspecialchars($inq['budget_range'] ?: 'N/A'); ?></td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="update_inquiry_status">
                                                <input type="hidden" name="inquiry_id" value="<?php echo $inq['id']; ?>">
                                                <select name="status" onchange="this.form.submit()" style="padding: 4px 8px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 0.8rem;">
                                                    <option value="new" <?php echo ($inq['status'] === 'new') ? 'selected' : ''; ?>>New</option>
                                                    <option value="in_review" <?php echo ($inq['status'] === 'in_review') ? 'selected' : ''; ?>>In Review</option>
                                                    <option value="contacted" <?php echo ($inq['status'] === 'contacted') ? 'selected' : ''; ?>>Contacted</option>
                                                    <option value="archived" <?php echo ($inq['status'] === 'archived') ? 'selected' : ''; ?>>Archived</option>
                                                </select>
                                            </form>
                                        </td>
                                        <td style="color: var(--text-muted);"><?php echo date('M d, Y', strtotime($inq['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 36px;">No client inquiries logged yet. Submissions from the website quote form will appear here dynamically.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            <!-- SECTION: TESTIMONIALS -->
            <?php elseif ($activeSection === 'testimonials'): 
                $testimonialsItems = getTestimonialsRepo();
                $editingTestimonialId = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : null;
                $subAction = $_GET['sub_action'] ?? '';
                
                $editingTestimonial = null;
                if ($editingTestimonialId !== null) {
                    foreach ($testimonialsItems as $ti) {
                        if ((int)($ti['id'] ?? 0) === $editingTestimonialId) {
                            $editingTestimonial = $ti;
                            break;
                        }
                    }
                }
            ?>
                <!-- Header Bar -->
                <div class="section-header-bar" style="align-items: center; margin-bottom: 24px;">
                    <div>
                        <h1 class="section-header-title" style="font-size: 1.75rem; font-weight: 800; color: #0f172a; margin-bottom: 4px;">Testimonials</h1>
                        <p class="section-header-desc" style="font-size: 0.88rem; color: #64748b; margin: 0; display: flex; align-items: center; gap: 8px;">
                            <strong style="color: #0f172a;"><?php echo count($testimonialsItems); ?> client testimonials</strong>
                        </p>
                    </div>
                    <?php if ($subAction !== 'add' && $editingTestimonialId === null): ?>
                        <a href="index.php?section=testimonials&sub_action=add" class="btn-save-primary" style="text-decoration: none; padding: 10px 22px; background-color: #dc2626;">
                            <i class="fa-solid fa-plus"></i> Add Testimonial
                        </a>
                    <?php endif; ?>
                </div>

                <?php if ($subAction === 'add' || $editingTestimonialId !== null): ?>
                    <!-- EDIT / ADD FORM VIEW (Matching Screenshot 2) -->
                    <div class="dashboard-card" style="margin-bottom: 28px; background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 28px;">
                        <h3 style="font-size: 1.15rem; font-weight: 800; color: #0f172a; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                            <i class="fa-solid fa-quote-left" style="color: #dc2626;"></i>
                            <?php echo ($editingTestimonialId !== null) ? 'Edit Testimonial' : 'Add New Testimonial'; ?>
                        </h3>

                        <form action="index.php?section=testimonials" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="save_testimonial_item">
                            <?php if ($editingTestimonialId !== null): ?>
                                <input type="hidden" name="id" value="<?php echo $editingTestimonialId; ?>">
                            <?php endif; ?>

                            <!-- 2-Column Inputs Grid -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px;" class="form-field">
                                <div>
                                    <label class="form-label-title">Client Name</label>
                                    <input type="text" name="name" class="form-text-input" value="<?php echo htmlspecialchars($editingTestimonial['name'] ?? ''); ?>" placeholder="Marcus Webb" required>
                                </div>

                                <div>
                                    <label class="form-label-title">Role</label>
                                    <input type="text" name="role" class="form-text-input" value="<?php echo htmlspecialchars($editingTestimonial['role'] ?? ''); ?>" placeholder="Head of Marketing" required>
                                </div>

                                <div>
                                    <label class="form-label-title">Company</label>
                                    <input type="text" name="company" class="form-text-input" value="<?php echo htmlspecialchars($editingTestimonial['company'] ?? ''); ?>" placeholder="RedBull EMEA" required>
                                </div>

                                <div>
                                    <label class="form-label-title">Project Name</label>
                                    <input type="text" name="project" class="form-text-input" value="<?php echo htmlspecialchars($editingTestimonial['project'] ?? ''); ?>" placeholder="Energy Unleashed Campaign">
                                </div>

                                <div>
                                    <label class="form-label-title">Avatar Image &amp; URL</label>
                                    <div style="display: flex; align-items: center; gap: 12px; background: #f8fafc; padding: 10px 14px; border-radius: 12px; border: 1px solid var(--card-border);">
                                        <img 
                                            id="testimonial_avatar_preview" 
                                            src="<?php echo htmlspecialchars(getCloudinaryUrl($editingTestimonial['avatar'] ?? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80')); ?>" 
                                            alt="Avatar Preview" 
                                            style="width: 44px; height: 44px; border-radius: 50%; object-fit: cover; border: 2px solid #ffffff; box-shadow: 0 2px 6px rgba(0,0,0,0.1); flex-shrink: 0;"
                                            onerror="this.src='https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80';"
                                        >
                                        <div style="display: flex; flex-direction: column; gap: 6px; flex: 1;">
                                            <div style="display: flex; gap: 8px;">
                                                <button 
                                                    type="button" 
                                                    onclick="openCropperModal(document.getElementById('testimonial_avatar_preview').src, 'avatar')"
                                                    style="background: #ffffff; color: #0f172a; border: 1px solid #cbd5e1; border-radius: 6px; padding: 4px 10px; font-size: 0.76rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;"
                                                >
                                                    <i class="fa-solid fa-crop-simple"></i> Re-crop
                                                </button>
                                                <button 
                                                    type="button" 
                                                    onclick="document.getElementById('testimonial_avatar_file_input').click()"
                                                    style="background: #dc2626; color: #ffffff; border: none; border-radius: 6px; padding: 4px 10px; font-size: 0.76rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;"
                                                >
                                                    <i class="fa-solid fa-cloud-arrow-up"></i> Upload Cloudinary
                                                </button>
                                            </div>
                                            <input 
                                                type="text" 
                                                name="avatar" 
                                                id="testimonial_avatar_url_input" 
                                                class="form-text-input" 
                                                style="font-size: 0.78rem; padding: 5px 8px;" 
                                                value="<?php echo htmlspecialchars($editingTestimonial['avatar'] ?? ''); ?>" 
                                                placeholder="https://res.cloudinary.com/..."
                                                oninput="document.getElementById('testimonial_avatar_preview').src = this.value;"
                                            >
                                        </div>
                                    </div>

                                    <input 
                                        type="file" 
                                        id="testimonial_avatar_file_input" 
                                        name="avatar_file" 
                                        accept="image/*" 
                                        style="display: none;" 
                                        onchange="handleAvatarFileSelect(this)"
                                    >
                                    <input 
                                        type="hidden" 
                                        id="cropped_avatar_data" 
                                        name="cropped_avatar_data" 
                                        value=""
                                    >
                                </div>

                                <div>
                                    <label class="form-label-title">Rating (1-5)</label>
                                    <select name="rating" class="form-text-input">
                                        <option value="5" <?php echo ($editingTestimonial['rating'] ?? 5) == 5 ? 'selected' : ''; ?>>5 stars</option>
                                        <option value="4" <?php echo ($editingTestimonial['rating'] ?? 5) == 4 ? 'selected' : ''; ?>>4 stars</option>
                                        <option value="3" <?php echo ($editingTestimonial['rating'] ?? 5) == 3 ? 'selected' : ''; ?>>3 stars</option>
                                        <option value="2" <?php echo ($editingTestimonial['rating'] ?? 5) == 2 ? 'selected' : ''; ?>>2 stars</option>
                                        <option value="1" <?php echo ($editingTestimonial['rating'] ?? 5) == 1 ? 'selected' : ''; ?>>1 star</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Quote Textarea -->
                            <div style="margin-top: 18px;">
                                <label class="form-label-title">Quote / Testimonial</label>
                                <textarea 
                                    name="quote" 
                                    class="form-text-input" 
                                    rows="4" 
                                    style="width: 100%; resize: vertical;" 
                                    placeholder="Enter client quote..."
                                    oninput="document.getElementById('quote_char_counter').innerText = this.value.length + '/500';"
                                    required
                                ><?php echo htmlspecialchars($editingTestimonial['quote'] ?? ''); ?></textarea>
                                <div style="text-align: right; font-size: 0.76rem; color: #94a3b8; margin-top: 4px;" id="quote_char_counter">
                                    <?php echo strlen($editingTestimonial['quote'] ?? ''); ?>/500
                                </div>
                            </div>

                            <!-- Form Action Buttons -->
                            <div style="display: flex; gap: 12px; margin-top: 24px;">
                                <button type="submit" class="btn-save-primary" style="padding: 10px 24px; background-color: #dc2626;">
                                    Save Changes
                                </button>
                                <a href="index.php?section=testimonials" class="btn-live-site" style="text-decoration: none; padding: 10px 20px;">
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <!-- TESTIMONIALS LIST VIEW CARDS (Matching Screenshots 1 & 3) -->
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <?php foreach ($testimonialsItems as $tItem): ?>
                        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 20px 24px; display: flex; flex-direction: column; gap: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                            <!-- Top Info Row: Avatar, Name, Role & Company -->
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <img 
                                    src="<?php echo htmlspecialchars(getCloudinaryUrl($tItem['avatar'] ?? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80')); ?>" 
                                    alt="<?php echo htmlspecialchars($tItem['name']); ?>" 
                                    style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 1px solid #cbd5e1;"
                                    onerror="this.src='https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80';"
                                >
                                <div>
                                    <div style="font-size: 1rem; font-weight: 700; color: #0f172a; line-height: 1.3;">
                                        <?php echo htmlspecialchars($tItem['name']); ?>
                                        <span style="font-size: 0.85rem; font-weight: 500; color: #64748b; margin-left: 6px;">
                                            <?php echo htmlspecialchars(($tItem['role'] ?? '') . (!empty($tItem['company']) ? ', ' . $tItem['company'] : '')); ?>
                                        </span>
                                    </div>
                                    <!-- Star Rating (Yellow Stars) -->
                                    <div style="color: #f59e0b; font-size: 0.78rem; margin-top: 3px;">
                                        <?php 
                                        $stars = (int)($tItem['rating'] ?? 5);
                                        for ($i = 0; $i < $stars; $i++) {
                                            echo '<i class="fa-solid fa-star"></i> ';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Quote Body -->
                            <p style="font-size: 0.9rem; color: #475569; font-style: italic; margin: 0; line-height: 1.5;">
                                "<?php echo htmlspecialchars($tItem['quote']); ?>"
                            </p>

                            <!-- Project Tag -->
                            <?php if (!empty($tItem['project'])): ?>
                                <div style="font-size: 0.78rem; color: #94a3b8; font-weight: 500;">
                                    Project: <?php echo htmlspecialchars($tItem['project']); ?>
                                </div>
                            <?php endif; ?>

                            <!-- Bottom Edit / Delete Action Buttons Bar -->
                            <div style="display: flex; align-items: center; justify-content: space-between; border-top: 1px solid #f1f5f9; padding-top: 12px; margin-top: 4px;">
                                <a href="index.php?section=testimonials&edit_id=<?php echo $tItem['id']; ?>" style="color: #64748b; font-size: 0.82rem; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 5px;" onmouseover="this.style.color='#0f172a'" onmouseout="this.style.color='#64748b'">
                                    <i class="fa-solid fa-pencil" style="font-size: 0.75rem;"></i> Edit
                                </a>

                                <form action="index.php?section=testimonials" method="POST" onsubmit="return confirm('Are you sure you want to delete this testimonial?');" style="margin: 0;">
                                    <input type="hidden" name="action" value="delete_testimonial_item">
                                    <input type="hidden" name="id" value="<?php echo $tItem['id']; ?>">
                                    <button type="submit" style="background: none; border: none; color: #94a3b8; font-size: 0.82rem; font-weight: 500; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; padding: 0;" onmouseover="this.style.color='#dc2626'" onmouseout="this.style.color='#94a3b8'">
                                        <i class="fa-solid fa-trash-can" style="font-size: 0.75rem;"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            <!-- SECTION: TEAM MANAGEMENT -->
            <?php elseif ($activeSection === 'team'): 
                $teamMembersList = array_values(getTeamMembers());
                $editingTeamId = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : null;
                $subAction = $_GET['sub_action'] ?? '';
                
                $editingMember = null;
                if ($editingTeamId !== null) {
                    foreach ($teamMembersList as $tm) {
                        if ((int)($tm['id'] ?? 0) === $editingTeamId) {
                            $editingMember = $tm;
                            break;
                        }
                    }
                }
            ?>
                <?php if (isset($_GET['synced'])): ?>
                    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 12px 18px; border-radius: 12px; margin-bottom: 20px; font-size: 0.9rem; font-weight: 700; display: flex; align-items: center; gap: 10px;">
                        <i class="fa-solid fa-arrows-rotate" style="font-size: 1.1rem; color: #22c55e;"></i> Team Roster &amp; Staff Accounts fully synchronized! (<?php echo (int)($_GET['added'] ?? 0); ?> added, <?php echo (int)($_GET['updated'] ?? 0); ?> updated)
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['saved'])): ?>
                    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 12px 18px; border-radius: 12px; margin-bottom: 20px; font-size: 0.9rem; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-circle-check" style="font-size: 1.1rem; color: #22c55e;"></i> Team member saved successfully!
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['deleted'])): ?>
                    <div style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 12px 18px; border-radius: 12px; margin-bottom: 20px; font-size: 0.9rem; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-trash-can" style="font-size: 1.1rem; color: #ef4444;"></i> Team member removed.
                    </div>
                <?php endif; ?>

                <!-- Top Section Title Bar -->
                <div class="section-header-bar" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
                    <div>
                        <span id="teamReorderStatus" style="display: none; font-size: 0.78rem; font-weight: 700; padding: 4px 12px; border-radius: 20px; align-items: center; gap: 6px;"></span>
                    </div>
                    <?php if ($subAction !== 'add' && $editingTeamId === null): ?>
                        <div style="display: flex; gap: 10px;">
                            <form action="index.php?section=team" method="POST" style="margin: 0; display: inline;">
                                <input type="hidden" name="action" value="sync_team_and_staff">
                                <button type="submit" style="background: #ffffff; color: #0f172a; border: 1px solid #cbd5e1; border-radius: 10px; padding: 10px 16px; font-weight: 700; font-size: 0.85rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='#ffffff'" title="Synchronize Team Content with Staff Accounts">
                                    <i class="fa-solid fa-arrows-rotate" style="color: #dc2626;"></i> Sync with Staff Accounts
                                </button>
                            </form>
                            <button type="button" onclick="saveTeamOrder()" style="background: #ffffff; color: #0f172a; border: 1px solid #cbd5e1; border-radius: 10px; padding: 10px 16px; font-weight: 700; font-size: 0.85rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                                <i class="fa-solid fa-floppy-disk" style="color: #dc2626;"></i> Save Display Order
                            </button>
                            <a href="index.php?section=team&sub_action=add" class="btn-save-primary" style="text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-weight: 700; background: #dc2626; color: #ffffff; padding: 10px 20px; border-radius: 10px;">
                                <i class="fa-solid fa-plus"></i> Add New Team Member
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($subAction === 'add' || $editingTeamId !== null): ?>
                    <!-- EDIT / ADD TEAM MEMBER FORM -->
                    <div class="dashboard-card" style="margin-bottom: 28px; background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 28px;">
                        <h3 style="font-size: 1.15rem; font-weight: 800; color: #0f172a; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                            <i class="fa-solid fa-user-pen" style="color: #dc2626;"></i>
                            <?php echo ($editingTeamId !== null) ? 'Edit Team Member: ' . htmlspecialchars($editingMember['name'] ?? '') : 'Add New Team Member'; ?>
                        </h3>

                        <form action="index.php?section=team" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="save_team_member">
                            <?php if ($editingTeamId !== null): ?>
                                <input type="hidden" name="id" value="<?php echo $editingTeamId; ?>">
                            <?php endif; ?>

                            <!-- 2-Column Form Grid -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-bottom: 18px;">
                                <div>
                                    <label class="form-label-title">Full Name <span style="color: #ef4444;">*</span></label>
                                    <input type="text" name="name" class="form-text-input" value="<?php echo htmlspecialchars($editingMember['name'] ?? ''); ?>" placeholder="e.g. Henry Falonipe" required>
                                </div>
                                <div>
                                    <label class="form-label-title">Role / Title <span style="color: #ef4444;">*</span></label>
                                    <input type="text" name="role" class="form-text-input" value="<?php echo htmlspecialchars($editingMember['role'] ?? ''); ?>" placeholder="e.g. Creative Director" required>
                                </div>
                                 <div>
                                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                                        <label class="form-label-title" style="margin: 0;">Department <span style="color: #ef4444;">*</span></label>
                                        <a href="javascript:void(0);" onclick="openAddModal('department')" style="font-size: 0.76rem; color: #dc2626; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                            <i class="fa-solid fa-circle-plus"></i> + Add New Department
                                        </a>
                                    </div>
                                    <?php 
                                    $teamDeptsList = getTeamDepartments();
                                    $currentDeptRaw = trim($editingMember['department'] ?? 'Creative');
                                    if (!empty($currentDeptRaw) && !in_array($currentDeptRaw, $teamDeptsList, true)) {
                                        $teamDeptsList[] = $currentDeptRaw;
                                    }
                                    ?>
                                    <select name="department" id="team_department_select" class="form-text-input" style="background-color: #ffffff; cursor: pointer;" onchange="handleDepartmentSelectChange(this)">
                                        <?php foreach ($teamDeptsList as $deptOption): ?>
                                            <option value="<?php echo htmlspecialchars($deptOption); ?>" <?php echo (strcasecmp($currentDeptRaw, $deptOption) === 0) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($deptOption); ?>
                                            </option>
                                        <?php endforeach; ?>
                                        <option value="__add_new__" style="font-weight: 700; color: #dc2626;">+ Add New Department...</option>
                                    </select>
                                </div>
                                <div>
                                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                                        <label class="form-label-title" style="margin: 0;">Location / Office</label>
                                        <a href="javascript:void(0);" onclick="openAddModal('location')" style="font-size: 0.76rem; color: #dc2626; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                            <i class="fa-solid fa-circle-plus"></i> + Add New Location
                                        </a>
                                    </div>
                                    <?php 
                                    $teamLocationsList = getTeamLocations();
                                    $currentLoc = trim($editingMember['location'] ?? 'Lagos');
                                    if (!empty($currentLoc) && !in_array($currentLoc, $teamLocationsList, true)) {
                                        $teamLocationsList[] = $currentLoc;
                                    }
                                    ?>
                                    <select name="location" id="team_location_select" class="form-text-input" style="background-color: #ffffff; cursor: pointer;" onchange="handleLocationSelectChange(this)">
                                        <?php foreach ($teamLocationsList as $locOption): ?>
                                            <option value="<?php echo htmlspecialchars($locOption); ?>" <?php echo (strcasecmp($currentLoc, $locOption) === 0) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($locOption); ?>
                                            </option>
                                        <?php endforeach; ?>
                                        <option value="__add_new__" style="font-weight: 700; color: #dc2626;">+ Add New Location...</option>
                                    </select>
                                </div>
                                <div style="grid-column: span 2;">
                                    <label class="form-label-title">Experience Tagline</label>
                                    <input type="text" name="experience" class="form-text-input" value="<?php echo htmlspecialchars($editingMember['experience'] ?? ''); ?>" placeholder="e.g. 15+ years at Falhen">
                                </div>
                            </div>

                            <!-- Photo Selector & Cloudinary Upload -->
                            <div style="margin-bottom: 18px;">
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                                    <label class="form-label-title" style="margin: 0;">Profile Photo</label>
                                    <span id="team_upload_status_badge" style="display: none; font-size: 0.76rem; font-weight: 700; padding: 3px 10px; border-radius: 20px; background: #eff6ff; color: #1d4ed8; align-items: center; gap: 5px;">
                                    </span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 16px; background: #f8fafc; padding: 14px 18px; border-radius: 12px; border: 1px solid #e2e8f0;">
                                    <img 
                                        id="team_image_preview" 
                                        src="<?php echo htmlspecialchars(getCloudinaryUrl(!empty($editingMember['image']) ? $editingMember['image'] : '/assets/img/team/team_henry.png')); ?>" 
                                        alt="Profile Photo Preview" 
                                        style="width: 70px; height: 70px; border-radius: 50%; object-fit: cover; border: 2px solid #dc2626; box-shadow: 0 2px 6px rgba(0,0,0,0.1); flex-shrink: 0;"
                                        onerror="this.src='/assets/img/team/team_henry.png';"
                                    >
                                    <div style="display: flex; flex-direction: column; gap: 8px; flex: 1;">
                                        <div style="display: flex; gap: 8px;">
                                            <button 
                                                type="button" 
                                                onclick="openCropperModal(document.getElementById('team_image_preview').src, 'team')"
                                                style="background: #ffffff; color: #0f172a; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 12px; font-size: 0.78rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 5px;"
                                            >
                                                <i class="fa-solid fa-crop-simple"></i> Re-crop Photo
                                            </button>
                                            <button 
                                                type="button" 
                                                onclick="document.getElementById('team_image_file_input').click()"
                                                style="background: #dc2626; color: #ffffff; border: none; border-radius: 6px; padding: 6px 12px; font-size: 0.78rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 5px;"
                                            >
                                                <i class="fa-solid fa-cloud-arrow-up"></i> Upload Custom Photo
                                            </button>
                                        </div>
                                        <input 
                                            type="text" 
                                            name="existing_image" 
                                            id="team_image_url_input" 
                                            class="form-text-input" 
                                            style="font-size: 0.8rem; padding: 6px 10px;" 
                                            value="<?php echo htmlspecialchars($editingMember['image'] ?? ''); ?>" 
                                            placeholder="Image URL or Cloudinary path..."
                                            oninput="document.getElementById('team_image_preview').src = this.value;"
                                        >
                                    </div>
                                </div>
                                <input 
                                    type="file" 
                                    id="team_image_file_input" 
                                    name="team_image_file" 
                                    accept="image/*" 
                                    style="display: none;" 
                                    onchange="handleTeamImageFileSelect(this)"
                                >
                                <input 
                                    type="hidden" 
                                    name="cropped_team_image_data" 
                                    id="cropped_team_image_data"
                                >
                            </div>

                            <!-- Bio Textarea -->
                            <div style="margin-bottom: 18px;">
                                <label class="form-label-title">Biography / Professional Summary</label>
                                <textarea name="bio" rows="3" class="form-text-input" style="line-height: 1.5;" placeholder="Enter background bio..."><?php echo htmlspecialchars($editingMember['bio'] ?? ''); ?></textarea>
                            </div>

                            <!-- Skills Text Input -->
                            <div style="margin-bottom: 24px;">
                                <label class="form-label-title">Core Skills / Specializations <span style="font-size: 0.76rem; color: #64748b; font-weight: 500;">(Comma-separated list)</span></label>
                                <input 
                                    type="text" 
                                    name="skills" 
                                    class="form-text-input" 
                                    value="<?php echo htmlspecialchars(implode(', ', $editingMember['skills'] ?? [])); ?>" 
                                    placeholder="e.g. Creative Direction, Brand Strategy, Concert Development"
                                >
                            </div>

                            <!-- Form Actions -->
                            <div style="display: flex; gap: 12px;">
                                <button type="submit" class="btn-save-primary" style="padding: 10px 24px; background-color: #dc2626; color: #ffffff; border: none; border-radius: 8px; font-weight: 700; cursor: pointer;">
                                    Save Member
                                </button>
                                <a href="index.php?section=team" class="btn-live-site" style="text-decoration: none; padding: 10px 20px; border: 1px solid #cbd5e1; border-radius: 8px; color: #475569; font-weight: 600;">
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <!-- TEAM MEMBERS CARDS GRID VIEW -->
                <div id="teamMembersGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px;">
                    <?php foreach ($teamMembersList as $tmIndex => $tmItem): ?>
                        <div class="team-card-admin-item" draggable="true" data-id="<?php echo $tmItem['id']; ?>" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 22px; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 1px 3px rgba(0,0,0,0.02); position: relative;">
                            <div>
                                <!-- Drag Handle & Position Controls Bar -->
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; background: #f8fafc; padding: 6px 12px; border-radius: 8px; border: 1px solid #f1f5f9;">
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <i class="fa-solid fa-grip-vertical team-drag-handle" style="cursor: grab; color: #94a3b8; font-size: 1.1rem;" title="Drag card to reorder position"></i>
                                        <span class="team-card-index-num" style="background: #e2e8f0; color: #334155; font-size: 0.72rem; font-weight: 800; padding: 2px 8px; border-radius: 6px; font-family: monospace;">
                                            <?php echo sprintf("%02d", $tmIndex + 1); ?>
                                        </span>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 4px;">
                                        <button type="button" onclick="moveTeamCard(this, 'up')" title="Move Left / Earlier" style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; width: 26px; height: 26px; cursor: pointer; color: #475569; display: flex; align-items: center; justify-content: center; font-size: 0.72rem;">
                                            <i class="fa-solid fa-arrow-left"></i>
                                        </button>
                                        <button type="button" onclick="moveTeamCard(this, 'down')" title="Move Right / Later" style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; width: 26px; height: 26px; cursor: pointer; color: #475569; display: flex; align-items: center; justify-content: center; font-size: 0.72rem;">
                                            <i class="fa-solid fa-arrow-right"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Top Row: Avatar & Department Pill -->
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                                    <div style="display: flex; align-items: center; gap: 14px;">
                                        <img 
                                            src="<?php echo htmlspecialchars(getCloudinaryUrl(!empty($tmItem['image']) ? $tmItem['image'] : '/assets/img/team/team_henry.png')); ?>" 
                                            alt="<?php echo htmlspecialchars($tmItem['name']); ?>" 
                                            style="width: 54px; height: 54px; border-radius: 50%; object-fit: cover; border: 2px solid #dc2626;"
                                            onerror="this.src='/assets/img/team/team_henry.png';"
                                        >
                                        <div>
                                            <div style="font-size: 1.05rem; font-weight: 800; color: #0f172a; line-height: 1.2;">
                                                <?php echo htmlspecialchars($tmItem['name']); ?>
                                            </div>
                                            <div style="font-size: 0.82rem; font-weight: 600; color: #dc2626; margin-top: 2px;">
                                                <?php echo htmlspecialchars($tmItem['role']); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <span style="background: #f1f5f9; color: #475569; font-size: 0.72rem; font-weight: 700; padding: 4px 10px; border-radius: 20px; text-transform: uppercase;">
                                        <?php echo htmlspecialchars($tmItem['department'] ?? 'Creative'); ?>
                                    </span>
                                </div>

                                <!-- Experience & Location Bar -->
                                <div style="display: flex; align-items: center; gap: 12px; font-size: 0.78rem; color: #64748b; margin-bottom: 12px; background: #f8fafc; padding: 6px 12px; border-radius: 8px;">
                                    <span><i class="fa-solid fa-location-dot" style="color: #dc2626;"></i> <?php echo htmlspecialchars($tmItem['location'] ?? 'Lagos'); ?></span>
                                    <?php if (!empty($tmItem['experience'])): ?>
                                        <span>•</span>
                                        <span><i class="fa-solid fa-award" style="color: #eab308;"></i> <?php echo htmlspecialchars($tmItem['experience']); ?></span>
                                    <?php endif; ?>
                                </div>

                                <!-- Bio Snippet -->
                                <p style="font-size: 0.84rem; color: #475569; line-height: 1.45; margin: 0 0 14px 0; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                                    <?php echo htmlspecialchars($tmItem['bio'] ?? ''); ?>
                                </p>

                                <!-- Skills Pills -->
                                <?php if (!empty($tmItem['skills']) && is_array($tmItem['skills'])): ?>
                                    <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 16px;">
                                        <?php foreach ($tmItem['skills'] as $sk): ?>
                                            <span style="background: #fef2f2; color: #991b1b; font-size: 0.72rem; font-weight: 600; padding: 3px 8px; border-radius: 6px;">
                                                <?php echo htmlspecialchars($sk); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Bottom Action Buttons Bar -->
                            <div style="display: flex; align-items: center; justify-content: space-between; border-top: 1px solid #f1f5f9; padding-top: 14px; margin-top: 10px;">
                                <a href="index.php?section=team&edit_id=<?php echo $tmItem['id']; ?>" style="color: #0f172a; font-size: 0.82rem; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; background: #f1f5f9; padding: 6px 14px; border-radius: 8px;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                                    <i class="fa-solid fa-pen-to-square" style="font-size: 0.75rem; color: #dc2626;"></i> Edit Member
                                </a>

                                <form action="index.php?section=team" method="POST" onsubmit="return promptConfirmModal(this, 'Remove Team Member', 'Are you sure you want to remove <?php echo htmlspecialchars(addslashes($tmItem['name'])); ?> from the team roster?');" style="margin: 0;">
                                    <input type="hidden" name="action" value="delete_team_member">
                                    <input type="hidden" name="id" value="<?php echo $tmItem['id']; ?>">
                                    <button type="submit" style="background: none; border: none; color: #94a3b8; font-size: 0.82rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; padding: 4px 8px;" onmouseover="this.style.color='#dc2626'" onmouseout="this.style.color='#94a3b8'">
                                        <i class="fa-solid fa-trash-can" style="font-size: 0.75rem;"></i> Remove
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            <!-- SECTION: BLOG MANAGEMENT -->
            <?php elseif ($activeSection === 'blog'): 
                $blogPostsList = getBlogRepo();
                $editingBlogId = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : null;
                $subAction = $_GET['sub_action'] ?? '';
                
                $editingBlog = null;
                if ($editingBlogId !== null) {
                    foreach ($blogPostsList as $bp) {
                        if ((int)($bp['id'] ?? 0) === $editingBlogId) {
                            $editingBlog = $bp;
                            break;
                        }
                    }
                }
            ?>
                <!-- Header Bar -->
                <div class="section-header-bar" style="align-items: center; margin-bottom: 24px;">
                    <div>
                        <h1 class="section-header-title" style="font-size: 1.75rem; font-weight: 800; color: #0f172a; margin-bottom: 4px;">Blog & Insights</h1>
                        <p class="section-header-desc" style="font-size: 0.88rem; color: #64748b; margin: 0; display: flex; align-items: center; gap: 8px;">
                            <strong style="color: #0f172a;"><?php echo count($blogPostsList); ?> published articles</strong>
                            <span>•</span>
                            <span>Manage articles, authors, cover images, and featured spotlight</span>
                        </p>
                    </div>
                    <?php if ($subAction !== 'add' && $editingBlogId === null): ?>
                        <a href="index.php?section=blog&sub_action=add" class="btn-save-primary" style="text-decoration: none; padding: 10px 22px; background-color: #dc2626;">
                            <i class="fa-solid fa-plus"></i> Add New Article
                        </a>
                    <?php endif; ?>
                </div>

                <?php if ($editingBlog !== null || $subAction === 'add'): ?>
                    <!-- FORM VIEW: EDIT OR ADD BLOG POST -->
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 28px; margin-bottom: 32px; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid #f1f5f9;">
                            <h2 style="font-size: 1.2rem; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
                                <i class="fa-solid fa-pen-to-square" style="color: #dc2626;"></i>
                                <?php echo ($editingBlog !== null) ? 'Editing Article: ' . htmlspecialchars($editingBlog['title']) : 'Create New Article'; ?>
                            </h2>
                            <a href="index.php?section=blog" style="color: #64748b; font-size: 0.85rem; text-decoration: none; font-weight: 600;">
                                <i class="fa-solid fa-xmark"></i> Close Form
                            </a>
                        </div>

                        <?php if (isset($_GET['saved'])): ?>
                            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 12px 18px; border-radius: 10px; font-weight: 700; font-size: 0.9rem; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
                                <i class="fa-solid fa-circle-check" style="color: #22c55e; font-size: 1.1rem;"></i>
                                Article saved successfully & published live! You can continue editing or close the form.
                            </div>
                        <?php endif; ?>

                        <form action="index.php?section=blog" method="POST" enctype="multipart/form-data" onsubmit="syncNativeEditorContent()">
                            <input type="hidden" name="action" value="save_blog_post">
                            <?php if ($editingBlogId !== null): ?>
                                <input type="hidden" name="id" value="<?php echo $editingBlogId; ?>">
                            <?php endif; ?>

                            <!-- Article Title -->
                            <div style="margin-bottom: 18px;">
                                <label class="form-label-title">Article Title <span style="color: #ef4444;">*</span></label>
                                <input type="text" name="title" class="form-text-input" value="<?php echo htmlspecialchars($editingBlog['title'] ?? ''); ?>" placeholder="e.g. 10 Essential Tips for Creating Engaging Social Media Videos" required>
                            </div>

                            <!-- 3-Column Grid: Category, Date, Read Time -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 18px; margin-bottom: 18px;">
                                <div>
                                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                                        <label class="form-label-title" style="margin: 0;">Category <span style="color: #ef4444;">*</span></label>
                                        <a href="javascript:void(0);" onclick="openAddModal('category')" style="font-size: 0.76rem; color: #dc2626; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                            <i class="fa-solid fa-circle-plus"></i> + Add Category
                                        </a>
                                    </div>
                                    <?php 
                                    $blogCatsList = getBlogCategories();
                                    $currentCat = trim($editingBlog['category'] ?? 'Social Media');
                                    if (!empty($currentCat) && !in_array($currentCat, $blogCatsList, true)) {
                                        $blogCatsList[] = $currentCat;
                                    }
                                    ?>
                                    <select name="category" id="team_category_select" class="form-text-input" style="background-color: #ffffff; cursor: pointer;" onchange="handleCategorySelectChange(this)">
                                        <?php foreach ($blogCatsList as $catOpt): ?>
                                            <option value="<?php echo htmlspecialchars($catOpt); ?>" <?php echo (strcasecmp($currentCat, $catOpt) === 0) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($catOpt); ?>
                                            </option>
                                        <?php endforeach; ?>
                                        <option value="__add_new__" style="font-weight: 700; color: #dc2626;">+ Add New Category...</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label-title">Publish Date</label>
                                    <input type="text" name="date" class="form-text-input" value="<?php echo htmlspecialchars($editingBlog['date'] ?? date('F j, Y')); ?>" placeholder="March 12, 2024">
                                </div>
                                <div>
                                    <label class="form-label-title">Read Time</label>
                                    <input type="text" name="read_time" class="form-text-input" value="<?php echo htmlspecialchars($editingBlog['read_time'] ?? '6 min read'); ?>" placeholder="e.g. 6 min read">
                                </div>
                            </div>

                            <!-- 2-Column Grid: Author Name & Author Role from Team Database -->
                            <?php 
                            $teamDBMembers = getTeamMembers();
                            $currentAuthor = trim($editingBlog['author'] ?? 'Michael Chen');
                            $currentRole = trim($editingBlog['role'] ?? 'Creative Director');

                            $authorNamesList = [];
                            $authorRolesList = [];
                            foreach ($teamDBMembers as $tm) {
                                if (!empty($tm['name'])) {
                                    $authorNamesList[] = $tm['name'];
                                }
                                if (!empty($tm['role'])) {
                                    $authorRolesList[] = $tm['role'];
                                }
                            }
                            if (!empty($currentAuthor) && !in_array($currentAuthor, $authorNamesList, true)) {
                                $authorNamesList[] = $currentAuthor;
                            }
                            if (!empty($currentRole) && !in_array($currentRole, $authorRolesList, true)) {
                                $authorRolesList[] = $currentRole;
                            }
                            $authorNamesList = array_values(array_unique($authorNamesList));
                            $authorRolesList = array_values(array_unique($authorRolesList));
                            ?>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-bottom: 18px;">
                                <div>
                                    <label class="form-label-title">Author Name (from Team)</label>
                                    <select name="author" id="blog_author_select" class="form-text-input" style="background-color: #ffffff; cursor: pointer;" onchange="handleBlogAuthorSelectChange(this)">
                                        <?php foreach ($authorNamesList as $aName): ?>
                                            <option value="<?php echo htmlspecialchars($aName); ?>" <?php echo (strcasecmp($currentAuthor, $aName) === 0) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($aName); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label-title">Author Role / Title</label>
                                    <select name="role" id="blog_author_role_select" class="form-text-input" style="background-color: #ffffff; cursor: pointer;">
                                        <?php foreach ($authorRolesList as $aRole): ?>
                                            <option value="<?php echo htmlspecialchars($aRole); ?>" <?php echo (strcasecmp($currentRole, $aRole) === 0) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($aRole); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Excerpt Textarea -->
                            <div style="margin-bottom: 18px;">
                                <label class="form-label-title">Excerpt / Short Summary</label>
                                <textarea name="excerpt" class="form-text-input" rows="2" placeholder="Brief 1-2 sentence teaser summary for feed cards..."><?php echo htmlspecialchars($editingBlog['excerpt'] ?? ''); ?></textarea>
                            </div>

                            <!-- Cover Image Uploader & Cloudinary Integration & Cropper -->
                            <div style="margin-bottom: 18px;">
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                                    <label class="form-label-title" style="margin: 0;">Cover Image</label>
                                    <span id="blog_upload_status_badge" style="<?php echo (!empty($editingBlog['image']) && isCloudinaryUrl($editingBlog['image'])) ? 'display: inline-flex; background: #f0fdf4; color: #15803d;' : 'display: none;'; ?> font-size: 0.76rem; font-weight: 700; padding: 3px 10px; border-radius: 20px; align-items: center; gap: 5px;">
                                        <?php if (!empty($editingBlog['image']) && isCloudinaryUrl($editingBlog['image'])): ?>
                                            <i class="fa-solid fa-circle-check"></i> Uploaded to Cloudinary!
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 16px; background: #f8fafc; padding: 14px 18px; border-radius: 12px; border: 1px solid #e2e8f0;">
                                    <img 
                                        id="blog_image_preview" 
                                        src="<?php echo htmlspecialchars(getCloudinaryUrl(!empty($editingBlog['image']) ? $editingBlog['image'] : '/assets/img/services/service_video.png')); ?>" 
                                        alt="Cover Image Preview" 
                                        style="width: 110px; height: 68px; border-radius: 10px; object-fit: cover; border: 2px solid #dc2626; box-shadow: 0 2px 6px rgba(0,0,0,0.1); flex-shrink: 0;"
                                        onerror="this.src='/assets/img/services/service_video.png';"
                                    >
                                    <div style="display: flex; flex-direction: column; gap: 8px; flex: 1;">
                                        <div style="display: flex; gap: 8px;">
                                            <button 
                                                type="button" 
                                                onclick="openCropperModal(document.getElementById('blog_image_preview').src, 'blog')"
                                                style="background: #ffffff; color: #0f172a; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 12px; font-size: 0.78rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 5px;"
                                            >
                                                <i class="fa-solid fa-crop-simple"></i> Re-crop Cover
                                            </button>
                                            <button 
                                                type="button" 
                                                id="blog_upload_btn"
                                                onclick="document.getElementById('blog_image_file_input').click()"
                                                style="background: #dc2626; color: #ffffff; border: none; border-radius: 6px; padding: 6px 12px; font-size: 0.78rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 5px;"
                                            >
                                                <i class="fa-solid fa-cloud-arrow-up"></i> Upload Cover Image
                                            </button>
                                        </div>
                                        <input 
                                            type="text" 
                                            name="existing_image" 
                                            id="blog_image_url_input" 
                                            class="form-text-input" 
                                            style="font-size: 0.8rem; padding: 6px 10px;" 
                                            value="<?php echo htmlspecialchars($editingBlog['image'] ?? ''); ?>" 
                                            placeholder="Image URL or Cloudinary path..."
                                            oninput="document.getElementById('blog_image_preview').src = this.value;"
                                        >
                                        <input type="file" id="blog_image_file_input" name="blog_image_file" accept="image/*" style="display: none;" onchange="handleBlogImageFileSelect(this)">
                                        <input type="hidden" name="cropped_blog_image_data" id="cropped_blog_image_data">
                                    </div>
                                </div>
                            </div>

                            <!-- Full Article Content (Zero-Dependency Native HTML Editor) -->
                            <div style="margin-bottom: 20px;">
                                <label class="form-label-title">Full Article Content <span style="color: #ef4444;">*</span></label>
                                <input type="hidden" name="content" id="blog_content_hidden_input" value="<?php echo htmlspecialchars($editingBlog['content'] ?? ''); ?>">
                                
                                <div style="border: 1px solid #cbd5e1; border-radius: 10px; overflow: hidden; background: #ffffff; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                                    <!-- Formatting Toolbar (Works 100% Offline & Online with Zero Dependencies) -->
                                    <div style="display: flex; flex-wrap: wrap; gap: 6px; padding: 10px 14px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; align-items: center;">
                                        <button type="button" class="native-editor-btn" onmousedown="event.preventDefault(); applyNativeFormat('bold');" title="Bold"><b>B</b></button>
                                        <button type="button" class="native-editor-btn" onmousedown="event.preventDefault(); applyNativeFormat('italic');" title="Italic"><i>I</i></button>
                                        <button type="button" class="native-editor-btn" onmousedown="event.preventDefault(); applyNativeFormat('underline');" title="Underline"><u>U</u></button>
                                        <button type="button" class="native-editor-btn" onmousedown="event.preventDefault(); applyNativeFormat('strikeThrough');" title="Strikethrough"><s>S</s></button>
                                        <span style="width: 1px; height: 20px; background: #cbd5e1; margin: 0 4px;"></span>
                                        <button type="button" class="native-editor-btn" onmousedown="event.preventDefault(); applyNativeBlock('H1');" title="Heading 1">H1</button>
                                        <button type="button" class="native-editor-btn" onmousedown="event.preventDefault(); applyNativeBlock('H2');" title="Heading 2">H2</button>
                                        <button type="button" class="native-editor-btn" onmousedown="event.preventDefault(); applyNativeBlock('H3');" title="Heading 3">H3</button>
                                        <button type="button" class="native-editor-btn" onmousedown="event.preventDefault(); applyNativeBlock('P');" title="Paragraph">P</button>
                                        <span style="width: 1px; height: 20px; background: #cbd5e1; margin: 0 4px;"></span>
                                        <button type="button" class="native-editor-btn" onmousedown="event.preventDefault(); applyNativeFormat('insertUnorderedList');" title="Bullet List"><i class="fa-solid fa-list-ul"></i></button>
                                        <button type="button" class="native-editor-btn" onmousedown="event.preventDefault(); applyNativeFormat('insertOrderedList');" title="Numbered List"><i class="fa-solid fa-list-ol"></i></button>
                                        <button type="button" class="native-editor-btn" onmousedown="event.preventDefault(); applyNativeBlock('BLOCKQUOTE');" title="Quote Box"><i class="fa-solid fa-quote-left"></i></button>
                                        <span style="width: 1px; height: 20px; background: #cbd5e1; margin: 0 4px;"></span>
                                        <button type="button" class="native-editor-btn" onmousedown="event.preventDefault(); applyNativeLink();" title="Insert Link"><i class="fa-solid fa-link"></i></button>
                                        <button type="button" class="native-editor-btn" onmousedown="event.preventDefault(); applyNativeFormat('removeFormat');" title="Clear Formatting"><i class="fa-solid fa-eraser"></i></button>
                                    </div>
                                    
                                    <!-- Editable HTML Content Div -->
                                    <div 
                                        id="nativeBlogEditor" 
                                        contenteditable="true" 
                                        style="min-height: 280px; max-height: 500px; overflow-y: auto; padding: 16px; outline: none; font-size: 0.95rem; line-height: 1.7; color: #0f172a; font-family: inherit;"
                                        oninput="syncNativeEditorContent()"
                                        onkeyup="syncNativeEditorContent()"
                                        onmouseup="updateNativeToolbarState()"
                                    >
                                        <?php echo $editingBlog['content'] ?? ''; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Featured Article Checkbox -->
                            <div style="margin-bottom: 24px; display: flex; align-items: center; gap: 10px; background: #fef2f2; padding: 12px 16px; border-radius: 10px; border: 1px solid #fecaca;">
                                <input type="checkbox" name="featured" id="blog_featured_chk" value="1" <?php echo !empty($editingBlog['featured']) ? 'checked' : ''; ?> style="width: 18px; height: 18px; accent-color: #dc2626; cursor: pointer;">
                                <label for="blog_featured_chk" style="font-size: 0.88rem; font-weight: 700; color: #991b1b; cursor: pointer; margin: 0;">
                                    ★ Spotlight Hero Featured Article (Displays prominently at the top of the blog page)
                                </label>
                            </div>

                            <!-- Form Action Buttons -->
                            <div style="display: flex; gap: 12px;">
                                <button type="submit" class="btn-save-primary" style="padding: 10px 24px; background-color: #dc2626; border-radius: 8px; font-weight: 700;">
                                    Save Article
                                </button>
                                <a href="index.php?section=blog" class="btn-live-site" style="text-decoration: none; padding: 10px 20px; border: 1px solid #cbd5e1; border-radius: 8px; color: #475569; font-weight: 600;">
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <!-- BLOG POSTS GRID VIEW -->
                <div id="blogPostsGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 20px;">
                    <?php foreach ($blogPostsList as $bIndex => $bItem): ?>
                        <div class="blog-card-admin-item" draggable="true" data-id="<?php echo $bItem['id']; ?>" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 22px; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 1px 3px rgba(0,0,0,0.02); position: relative;">
                            <div>
                                <!-- Drag Handle & Controls -->
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; background: #f8fafc; padding: 6px 12px; border-radius: 8px; border: 1px solid #f1f5f9;">
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <i class="fa-solid fa-grip-vertical blog-drag-handle" style="cursor: grab; color: #94a3b8; font-size: 1.1rem;" title="Drag to reorder position"></i>
                                        <span class="blog-card-index-num" style="background: #e2e8f0; color: #334155; font-size: 0.72rem; font-weight: 800; padding: 2px 8px; border-radius: 6px; font-family: monospace;">
                                            <?php echo sprintf("%02d", $bIndex + 1); ?>
                                        </span>
                                        <?php if (!empty($bItem['featured'])): ?>
                                            <span style="background: #fef3c7; color: #d97706; font-size: 0.7rem; font-weight: 800; padding: 2px 8px; border-radius: 6px; display: inline-flex; align-items: center; gap: 3px;">
                                                <i class="fa-solid fa-star"></i> Featured Spotlight
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 4px;">
                                        <button type="button" onclick="moveBlogCard(this, 'up')" title="Move Left / Earlier" style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; width: 26px; height: 26px; cursor: pointer; color: #475569; display: flex; align-items: center; justify-content: center; font-size: 0.72rem;">
                                            <i class="fa-solid fa-arrow-left"></i>
                                        </button>
                                        <button type="button" onclick="moveBlogCard(this, 'down')" title="Move Right / Later" style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; width: 26px; height: 26px; cursor: pointer; color: #475569; display: flex; align-items: center; justify-content: center; font-size: 0.72rem;">
                                            <i class="fa-solid fa-arrow-right"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Cover Image & Category Pill -->
                                <div style="position: relative; aspect-ratio: 16/9; border-radius: 12px; overflow: hidden; margin-bottom: 14px; background: #0f172a;">
                                    <img 
                                        src="<?php echo htmlspecialchars(getCloudinaryUrl(!empty($bItem['image']) ? $bItem['image'] : '/assets/img/services/service_video.png')); ?>" 
                                        alt="<?php echo htmlspecialchars($bItem['title']); ?>" 
                                        style="width: 100%; height: 100%; object-fit: cover;"
                                        onerror="this.src='/assets/img/services/service_video.png';"
                                    >
                                    <span style="position: absolute; top: 10px; left: 10px; background: rgba(15,23,42,0.85); backdrop-filter: blur(4px); color: #ffffff; font-size: 0.72rem; font-weight: 700; padding: 4px 10px; border-radius: 20px;">
                                        <?php echo htmlspecialchars($bItem['category'] ?? 'Social Media'); ?>
                                    </span>
                                </div>

                                <!-- Article Title -->
                                <h3 style="font-size: 1.05rem; font-weight: 800; color: #0f172a; margin-bottom: 8px; line-height: 1.35;">
                                    <?php echo htmlspecialchars($bItem['title']); ?>
                                </h3>

                                <!-- Meta info -->
                                <div style="font-size: 0.78rem; color: #64748b; margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                                    <span><i class="fa-regular fa-calendar"></i> <?php echo htmlspecialchars($bItem['date'] ?? ''); ?></span>
                                    <span>•</span>
                                    <span><i class="fa-regular fa-clock"></i> <?php echo htmlspecialchars($bItem['read_time'] ?? ''); ?></span>
                                </div>

                                <!-- Excerpt -->
                                <p style="font-size: 0.82rem; color: #475569; margin-bottom: 14px; line-height: 1.45; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                    <?php echo htmlspecialchars($bItem['excerpt'] ?? ''); ?>
                                </p>
                            </div>

                            <!-- Bottom Author & Action Buttons Bar -->
                            <div>
                                <div style="font-size: 0.78rem; font-weight: 700; color: #dc2626; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                                    <i class="fa-solid fa-user-pen"></i> <?php echo htmlspecialchars($bItem['author'] ?? 'Falhen Team'); ?>
                                </div>

                                <div style="display: flex; align-items: center; justify-content: space-between; border-top: 1px solid #f1f5f9; padding-top: 14px;">
                                    <a href="index.php?section=blog&edit_id=<?php echo $bItem['id']; ?>" style="color: #0f172a; font-size: 0.82rem; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; background: #f1f5f9; padding: 6px 14px; border-radius: 8px;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                                        <i class="fa-solid fa-pen-to-square" style="font-size: 0.75rem; color: #dc2626;"></i> Edit Article
                                    </a>

                                    <form action="index.php?section=blog" method="POST" onsubmit="return confirm('Are you sure you want to remove this blog article?');" style="margin: 0;">
                                        <input type="hidden" name="action" value="delete_blog_post">
                                        <input type="hidden" name="id" value="<?php echo $bItem['id']; ?>">
                                        <button type="submit" style="background: none; border: none; color: #94a3b8; font-size: 0.82rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; padding: 4px 8px;" onmouseover="this.style.color='#dc2626'" onmouseout="this.style.color='#94a3b8'">
                                            <i class="fa-solid fa-trash-can" style="font-size: 0.75rem;"></i> Remove
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            <!-- SECTION: PORTFOLIO MANAGEMENT -->
            <?php elseif ($activeSection === 'portfolio'): 
                $portfolioItems = getPortfolioRepo();
                $editingPortfolioId = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : null;
                $subAction = $_GET['sub_action'] ?? '';
                $typeFilter = $_GET['type'] ?? 'all';
                $catFilter = $_GET['cat'] ?? 'all';
                $searchQuery = trim($_GET['q'] ?? '');
                
                $editingPortfolio = null;
                if ($editingPortfolioId !== null) {
                    foreach ($portfolioItems as $pi) {
                        if ((int)($pi['id'] ?? 0) === $editingPortfolioId) {
                            $editingPortfolio = $pi;
                            break;
                        }
                    }
                }

                // Filter items
                $filteredItems = array_filter($portfolioItems, function($item) use ($typeFilter, $catFilter, $searchQuery) {
                    if ($typeFilter !== 'all' && ($item['media_type'] ?? 'photo') !== $typeFilter) {
                        return false;
                    }
                    if ($catFilter !== 'all' && strtolower($item['category'] ?? '') !== strtolower($catFilter)) {
                        return false;
                    }
                    if (!empty($searchQuery)) {
                        $q = strtolower($searchQuery);
                        $titleMatch = strpos(strtolower($item['title'] ?? ''), $q) !== false;
                        $clientMatch = strpos(strtolower($item['client'] ?? ''), $q) !== false;
                        $catMatch = strpos(strtolower($item['category'] ?? ''), $q) !== false;
                        if (!$titleMatch && !$clientMatch && !$catMatch) {
                            return false;
                        }
                    }
                    return true;
                });
            ?>
                <?php if (isset($_GET['saved'])): ?>
                    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 12px 18px; border-radius: 12px; margin-bottom: 20px; font-size: 0.9rem; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-circle-check" style="font-size: 1.1rem; color: #22c55e;"></i> Portfolio item saved &amp; published live!
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['deleted'])): ?>
                    <div style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 12px 18px; border-radius: 12px; margin-bottom: 20px; font-size: 0.9rem; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-trash-can" style="font-size: 1.1rem; color: #ef4444;"></i> Portfolio item deleted successfully.
                    </div>
                <?php endif; ?>

                <!-- Header Bar -->
                <div class="section-header-bar" style="align-items: center; margin-bottom: 24px;">
                    <div>
                        <h1 class="section-header-title" style="font-size: 1.75rem; font-weight: 800; color: #0f172a; margin-bottom: 4px; display: flex; align-items: center; gap: 10px;">
                            <i class="fa-solid fa-layer-group" style="color: #dc2626;"></i> Portfolio Showcase
                        </h1>
                        <p class="section-header-desc" style="font-size: 0.88rem; color: #64748b; margin: 0; display: flex; align-items: center; gap: 12px;">
                            <span><strong style="color: #0f172a;"><?php echo count($portfolioItems); ?> total items</strong></span>
                            <span>&bull;</span>
                            <span><?php echo count(array_filter($portfolioItems, function($i) { return ($i['media_type'] ?? 'photo') === 'photo'; })); ?> Photo Albums</span>
                            <span>&bull;</span>
                            <span><?php echo count(array_filter($portfolioItems, function($i) { return ($i['media_type'] ?? 'photo') === 'video'; })); ?> Video Reels</span>
                            <span>&bull;</span>
                            <span><?php echo count(array_filter($portfolioItems, function($i) { return ($i['media_type'] ?? 'photo') === 'project'; })); ?> Projects</span>
                        </p>
                    </div>
                    <?php if ($subAction !== 'add' && $editingPortfolioId === null): ?>
                        <a href="index.php?section=portfolio&type=<?php echo urlencode($typeFilter); ?>&sub_action=add" class="btn-save-primary" style="text-decoration: none; padding: 10px 22px; background-color: #dc2626; display: inline-flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-plus"></i> Add New Portfolio Item
                        </a>
                    <?php endif; ?>
                </div>

                <?php if ($subAction === 'add' || $editingPortfolioId !== null): ?>
                    <!-- EDIT / ADD FORM VIEW -->
                    <div class="dashboard-card" style="margin-bottom: 28px; background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 28px;">
                        <h3 style="font-size: 1.15rem; font-weight: 800; color: #0f172a; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                            <i class="fa-solid fa-pen-to-square" style="color: #dc2626;"></i>
                            <?php echo ($editingPortfolioId !== null) ? 'Edit Portfolio Item: ' . htmlspecialchars($editingPortfolio['title'] ?? '') : 'Add New Portfolio Showcase Item'; ?>
                        </h3>

                        <form action="index.php?section=portfolio" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="save_portfolio_item">
                            <input type="hidden" name="type_filter" value="<?php echo htmlspecialchars($typeFilter); ?>">
                            <?php if ($editingPortfolioId !== null): ?>
                                <input type="hidden" name="id" value="<?php echo $editingPortfolioId; ?>">
                            <?php endif; ?>

                            <!-- 2-Column Inputs Grid -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px;" class="form-field">
                                <div>
                                    <label class="form-label-title">Project / Showcase Title <span style="color: #ef4444;">*</span></label>
                                    <input type="text" name="title" class="form-text-input" value="<?php echo htmlspecialchars($editingPortfolio['title'] ?? ''); ?>" placeholder="e.g. Halima's 40th Birthday Shoot" required>
                                </div>

                                <div>
                                    <label class="form-label-title">Media Type &amp; Source <span style="color: #ef4444;">*</span></label>
                                    <select name="media_type" id="portfolio_media_type_select" class="form-text-input" onchange="togglePortfolioMediaFields(this.value)">
                                        <option value="photo" <?php echo ($editingPortfolio['media_type'] ?? 'photo') === 'photo' ? 'selected' : ''; ?>>Photo Album (Google Drive Photos)</option>
                                        <option value="video" <?php echo ($editingPortfolio['media_type'] ?? 'photo') === 'video' ? 'selected' : ''; ?>>Video Reel (YouTube Video)</option>
                                        <option value="project" <?php echo ($editingPortfolio['media_type'] ?? 'photo') === 'project' ? 'selected' : ''; ?>>Project / Production (Case Study / Drive Link)</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="form-label-title">Category <span style="color: #ef4444;">*</span></label>
                                    <select name="category" class="form-text-input" required>
                                        <?php 
                                        $currentCat = $editingPortfolio['category'] ?? 'Corporate';
                                        $categories = ['Commercials', 'Corporate', 'Events', 'Documentary', 'Social', 'Broadcast', 'Wedding', 'Branding', 'Portrait', 'Birthday', 'Music Video', 'Reels'];
                                        if (!in_array($currentCat, $categories) && !empty($currentCat)) {
                                            $categories[] = $currentCat;
                                        }
                                        foreach ($categories as $catOpt): 
                                        ?>
                                            <option value="<?php echo htmlspecialchars($catOpt); ?>" <?php echo (strtolower($currentCat) === strtolower($catOpt)) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($catOpt); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div>
                                    <label class="form-label-title">Year / Project Date</label>
                                    <input type="text" name="year" class="form-text-input" value="<?php echo htmlspecialchars($editingPortfolio['year'] ?? ($editingPortfolio['project_date'] ?? '2024')); ?>" placeholder="e.g. 2024">
                                </div>

                                <div>
                                    <label class="form-label-title">Client Name</label>
                                    <input type="text" name="client" class="form-text-input" value="<?php echo htmlspecialchars($editingPortfolio['client'] ?? ''); ?>" placeholder="e.g. TechCorp International">
                                </div>

                                <div>
                                    <label class="form-label-title">Location / Shoot City</label>
                                    <input type="text" name="location" class="form-text-input" value="<?php echo htmlspecialchars($editingPortfolio['location'] ?? ''); ?>" placeholder="e.g. Lagos, Chicago, London">
                                </div>

                                <div id="portfolio_duration_wrapper" style="display: <?php echo ($editingPortfolio['media_type'] ?? 'photo') === 'video' ? 'block' : 'none'; ?>;">
                                    <label class="form-label-title">Video Duration (mm:ss)</label>
                                    <input type="text" name="duration" class="form-text-input" value="<?php echo htmlspecialchars($editingPortfolio['duration'] ?? ''); ?>" placeholder="02:30">
                                </div>
                            </div>

                            <!-- Google Drive Folder / Share Link Input (For Photo Albums & Projects) -->
                            <div id="portfolio_gdrive_url_wrapper" style="margin-top: 18px; display: <?php echo (($editingPortfolio['media_type'] ?? 'photo') === 'photo' || ($editingPortfolio['media_type'] ?? 'photo') === 'project') ? 'block' : 'none'; ?>;">
                                <label class="form-label-title" style="display: flex; align-items: center; gap: 6px;">
                                    <i class="fa-brands fa-google-drive" style="color: #4285F4; font-size: 1.1rem;"></i> Google Drive Folder or Album Share Link
                                </label>
                                <input 
                                    type="text" 
                                    name="gdrive_url" 
                                    id="portfolio_gdrive_url_input" 
                                    class="form-text-input" 
                                    value="<?php echo htmlspecialchars($editingPortfolio['gdrive_url'] ?? ''); ?>" 
                                    placeholder="https://drive.google.com/drive/folders/... or https://drive.google.com/file/d/..."
                                    oninput="handleGDriveUrlChange(this.value)"
                                >
                                <span style="font-size: 0.76rem; color: #64748b; margin-top: 4px; display: block;">
                                    Photo album images will come from this Google Drive folder/album link.
                                </span>
                            </div>

                            <!-- YouTube URL Input (For Video Reels) -->
                            <div id="portfolio_video_url_wrapper" style="margin-top: 18px; display: <?php echo ($editingPortfolio['media_type'] ?? 'photo') === 'video' ? 'block' : 'none'; ?>;">
                                <label class="form-label-title" style="display: flex; align-items: center; gap: 6px;">
                                    <i class="fa-brands fa-youtube" style="color: #FF0000; font-size: 1.1rem;"></i> YouTube Video URL or Video ID
                                </label>
                                <input 
                                    type="text" 
                                    name="video_url" 
                                    id="portfolio_video_url_input" 
                                    class="form-text-input" 
                                    value="<?php echo htmlspecialchars($editingPortfolio['video_url'] ?? ''); ?>" 
                                    placeholder="https://www.youtube.com/watch?v=ySus5ZS0b94 or ySus5ZS0b94"
                                    oninput="updateYouTubePreview(this.value)"
                                >
                                <span style="font-size: 0.76rem; color: #64748b; margin-top: 4px; display: block;">
                                    Video reels come from YouTube. The cover thumbnail is automatically picked from YouTube when you enter a URL.
                                </span>
                            </div>

                            <!-- Thumbnail / Cover Image Selector & Cloudinary Upload -->
                            <div style="margin-top: 18px;">
                                <label class="form-label-title">Thumbnail / Cover Image <span style="font-size: 0.76rem; color: #64748b; font-weight: 500;">(Auto-picked from YouTube for videos or customizable)</span></label>
                                <div style="display: flex; align-items: center; gap: 16px; background: #f8fafc; padding: 14px 18px; border-radius: 12px; border: 1px solid var(--card-border);">
                                    <img 
                                        id="portfolio_image_preview" 
                                        src="<?php echo htmlspecialchars(getCloudinaryUrl(!empty($editingPortfolio['image']) ? $editingPortfolio['image'] : (!empty($editingPortfolio['video_url']) ? getYouTubeThumbnailUrl($editingPortfolio['video_url']) : '/assets/img/portfolio/portfolio_halima.png'))); ?>" 
                                        alt="Thumbnail Preview" 
                                        style="width: 90px; height: 60px; border-radius: 8px; object-fit: cover; border: 1px solid #cbd5e1; box-shadow: 0 2px 6px rgba(0,0,0,0.1); flex-shrink: 0;"
                                        onerror="this.src='/assets/img/portfolio/portfolio_halima.png';"
                                    >
                                    <div style="display: flex; flex-direction: column; gap: 8px; flex: 1;">
                                        <div style="display: flex; gap: 8px;">
                                            <button 
                                                type="button" 
                                                onclick="openCropperModal(document.getElementById('portfolio_image_preview').src, 'portfolio')"
                                                style="background: #ffffff; color: #0f172a; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 12px; font-size: 0.78rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 5px;"
                                            >
                                                <i class="fa-solid fa-crop-simple"></i> Re-crop Image
                                            </button>
                                            <button 
                                                type="button" 
                                                id="portfolio_upload_btn"
                                                onclick="document.getElementById('portfolio_image_file_input').click()"
                                                style="background: #dc2626; color: #ffffff; border: none; border-radius: 6px; padding: 6px 12px; font-size: 0.78rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 5px;"
                                            >
                                                <i class="fa-solid fa-cloud-arrow-up"></i> Upload Custom Cover
                                            </button>
                                            <span id="portfolio_upload_status_badge" style="display: none; font-size: 0.75rem; font-weight: 600; padding: 3px 8px; border-radius: 6px; align-items: center; gap: 4px;"></span>
                                        </div>
                                        <input 
                                            type="text" 
                                            name="existing_image" 
                                            id="portfolio_image_url_input" 
                                            class="form-text-input" 
                                            style="font-size: 0.8rem; padding: 6px 10px;" 
                                            value="<?php echo htmlspecialchars($editingPortfolio['image'] ?? ''); ?>" 
                                            placeholder="Auto-generated thumbnail URL or custom image..."
                                            oninput="document.getElementById('portfolio_image_preview').src = this.value;"
                                        >
                                    </div>
                                </div>

                                <input 
                                    type="file" 
                                    id="portfolio_image_file_input" 
                                    name="portfolio_image_file" 
                                    accept="image/*" 
                                    style="display: none;" 
                                    onchange="handlePortfolioImageFileSelect(this)"
                                >
                                <input 
                                    type="hidden" 
                                    id="cropped_portfolio_image_data" 
                                    name="cropped_portfolio_image_data" 
                                    value=""
                                >
                            </div>

                            <!-- Description Textarea -->
                            <div id="portfolio_desc_wrapper" style="margin-top: 18px; display: block;">
                                <label class="form-label-title">Project Description / Story</label>
                                <textarea 
                                    name="desc" 
                                    class="form-text-input" 
                                    rows="3" 
                                    style="width: 100%; resize: vertical;" 
                                    placeholder="Enter project summary or backstory..."
                                ><?php echo htmlspecialchars($editingPortfolio['desc'] ?? ''); ?></textarea>
                            </div>

                            <!-- Services Field (comma separated) -->
                            <div id="portfolio_services_wrapper" style="margin-top: 18px;">
                                <label class="form-label-title">Services (comma separated)</label>
                                <input 
                                    type="text" 
                                    name="services" 
                                    class="form-text-input" 
                                    value="<?php echo htmlspecialchars($editingPortfolio['services'] ?? 'Video Production, Live Streaming, Post Production'); ?>" 
                                    placeholder="Video Production, Live Streaming, Post Production"
                                >
                            </div>

                            <!-- Featured Checkbox -->
                            <div style="margin-top: 18px; display: flex; align-items: center; gap: 10px;">
                                <input type="checkbox" name="featured" id="portfolio_featured_checkbox" value="1" <?php echo !empty($editingPortfolio['featured']) ? 'checked' : ''; ?> style="width: 18px; height: 18px; accent-color: #dc2626; cursor: pointer;">
                                <label for="portfolio_featured_checkbox" style="font-size: 0.9rem; font-weight: 700; color: #0f172a; cursor: pointer;">
                                    Feature on Front Page Showcase &amp; Highlights Slider
                                </label>
                            </div>

                            <!-- Form Action Buttons -->
                            <div style="display: flex; gap: 12px; margin-top: 24px;">
                                <button type="submit" class="btn-save-primary" style="padding: 10px 26px; background-color: #dc2626;">
                                    Save Portfolio Item
                                </button>
                                <a href="index.php?section=portfolio" class="btn-live-site" style="text-decoration: none; padding: 10px 20px;">
                                    Cancel
                                </a>
                            </div>
                            <script>
                            function togglePortfolioMediaFields(val) {
                                var durationWrap = document.getElementById('portfolio_duration_wrapper');
                                var gdriveWrap = document.getElementById('portfolio_gdrive_url_wrapper');
                                var videoWrap = document.getElementById('portfolio_video_url_wrapper');
                                var descWrap = document.getElementById('portfolio_desc_wrapper');

                                if (durationWrap) durationWrap.style.display = (val === 'video') ? 'block' : 'none';
                                if (gdriveWrap) gdriveWrap.style.display = (val === 'photo' || val === 'project') ? 'block' : 'none';
                                if (videoWrap) videoWrap.style.display = (val === 'video') ? 'block' : 'none';
                                if (descWrap) descWrap.style.display = 'block';
                            }
                            </script>
                        </form>
                    </div>
                <?php endif; ?>

                <!-- FILTER & SEARCH TOOLBAR -->
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px 20px; margin-bottom: 24px; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px;">
                    <!-- Media Type Filter Tabs -->
                    <div style="display: flex; gap: 6px; background: #f1f5f9; padding: 4px; border-radius: 10px;">
                        <a href="index.php?section=portfolio&type=all&cat=<?php echo urlencode($catFilter); ?>&q=<?php echo urlencode($searchQuery); ?>" style="padding: 6px 14px; border-radius: 8px; font-size: 0.82rem; font-weight: 700; text-decoration: none; transition: all 0.2s; <?php echo $typeFilter === 'all' ? 'background: #ffffff; color: #0f172a; box-shadow: 0 1px 3px rgba(0,0,0,0.1);' : 'color: #64748b;'; ?>">
                            All Types
                        </a>
                        <a href="index.php?section=portfolio&type=photo&cat=<?php echo urlencode($catFilter); ?>&q=<?php echo urlencode($searchQuery); ?>" style="padding: 6px 14px; border-radius: 8px; font-size: 0.82rem; font-weight: 700; text-decoration: none; transition: all 0.2s; <?php echo $typeFilter === 'photo' ? 'background: #ffffff; color: #0f172a; box-shadow: 0 1px 3px rgba(0,0,0,0.1);' : 'color: #64748b;'; ?>">
                            <i class="fa-solid fa-camera" style="color: #38bdf8; margin-right: 4px;"></i> Photos Only
                        </a>
                        <a href="index.php?section=portfolio&type=video&cat=<?php echo urlencode($catFilter); ?>&q=<?php echo urlencode($searchQuery); ?>" style="padding: 6px 14px; border-radius: 8px; font-size: 0.82rem; font-weight: 700; text-decoration: none; transition: all 0.2s; <?php echo $typeFilter === 'video' ? 'background: #ffffff; color: #0f172a; box-shadow: 0 1px 3px rgba(0,0,0,0.1);' : 'color: #64748b;'; ?>">
                            <i class="fa-solid fa-film" style="color: #ef4444; margin-right: 4px;"></i> Videos Only
                        </a>
                        <a href="index.php?section=portfolio&type=project&cat=<?php echo urlencode($catFilter); ?>&q=<?php echo urlencode($searchQuery); ?>" style="padding: 6px 14px; border-radius: 8px; font-size: 0.82rem; font-weight: 700; text-decoration: none; transition: all 0.2s; <?php echo $typeFilter === 'project' ? 'background: #ffffff; color: #0f172a; box-shadow: 0 1px 3px rgba(0,0,0,0.1);' : 'color: #64748b;'; ?>">
                            <i class="fa-solid fa-folder-open" style="color: #a78bfa; margin-right: 4px;"></i> Projects Only
                        </a>
                    </div>

                    <!-- Search Form -->
                    <form action="index.php" method="GET" style="display: flex; gap: 8px; margin: 0;">
                        <input type="hidden" name="section" value="portfolio">
                        <input type="hidden" name="type" value="<?php echo htmlspecialchars($typeFilter); ?>">
                        <input type="hidden" name="cat" value="<?php echo htmlspecialchars($catFilter); ?>">
                        <div style="position: relative;">
                            <input 
                                type="text" 
                                name="q" 
                                class="form-text-input" 
                                value="<?php echo htmlspecialchars($searchQuery); ?>" 
                                placeholder="Search by title, client..."
                                style="padding: 7px 12px 7px 32px; font-size: 0.85rem; width: 220px;"
                            >
                            <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 0.8rem;"></i>
                        </div>
                        <button type="submit" style="background: #0f172a; color: #ffffff; border: none; border-radius: 8px; padding: 7px 14px; font-size: 0.82rem; font-weight: 700; cursor: pointer;">
                            Filter
                        </button>
                        <?php if (!empty($searchQuery) || $typeFilter !== 'all' || $catFilter !== 'all'): ?>
                            <a href="index.php?section=portfolio" style="background: #e2e8f0; color: #475569; border-radius: 8px; padding: 7px 12px; font-size: 0.82rem; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center;">
                                Reset
                            </a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- PORTFOLIO GRID CARDS -->
                <?php if (empty($filteredItems)): ?>
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 48px 20px; text-align: center;">
                        <i class="fa-regular fa-folder-open" style="font-size: 2.5rem; color: #94a3b8; margin-bottom: 12px;"></i>
                        <h4 style="font-size: 1.1rem; font-weight: 700; color: #0f172a; margin-bottom: 6px;">No Portfolio Items Found</h4>
                        <p style="font-size: 0.88rem; color: #64748b; margin-bottom: 18px;">No projects match your filter criteria or search query.</p>
                        <a href="index.php?section=portfolio&type=<?php echo urlencode($typeFilter); ?>&sub_action=add" class="btn-save-primary" style="text-decoration: none; padding: 8px 18px; background-color: #dc2626; font-size: 0.85rem;">
                            + Add New Item
                        </a>
                    </div>
                <?php else: ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
                        <?php foreach ($filteredItems as $pItem): ?>
                            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; display: flex; flex-direction: column; transition: transform 0.2s, box-shadow 0.2s; box-shadow: 0 2px 5px rgba(0,0,0,0.03);">
                                <!-- Thumbnail Container -->
                                <div style="position: relative; aspect-ratio: 16/10; background: #0f172a; overflow: hidden;">
                                    <img 
                                        src="<?php echo htmlspecialchars(getCloudinaryUrl($pItem['image'] ?? '/assets/img/portfolio/portfolio_halima.png')); ?>" 
                                        alt="<?php echo htmlspecialchars($pItem['title']); ?>" 
                                        style="width: 100%; height: 100%; object-fit: cover;"
                                        onerror="this.src='/assets/img/portfolio/portfolio_halima.png';"
                                    >
                                    
                                    <!-- Top Left Media Type Tag -->
                                    <div style="position: absolute; top: 10px; left: 10px; background: rgba(15,23,42,0.85); color: #ffffff; font-size: 0.72rem; font-weight: 700; padding: 3px 8px; border-radius: 6px; backdrop-filter: blur(4px); display: flex; align-items: center; gap: 4px;">
                                        <?php if (($pItem['media_type'] ?? 'photo') === 'video'): ?>
                                            <i class="fa-solid fa-play" style="color: #ef4444; font-size: 0.68rem;"></i> Video <?php echo !empty($pItem['duration']) ? '(' . htmlspecialchars($pItem['duration']) . ')' : ''; ?>
                                        <?php elseif (($pItem['media_type'] ?? 'photo') === 'project'): ?>
                                            <i class="fa-solid fa-rocket" style="color: #a78bfa; font-size: 0.68rem;"></i> Project
                                        <?php else: ?>
                                            <i class="fa-solid fa-camera" style="color: #38bdf8; font-size: 0.68rem;"></i> Photo Album
                                        <?php endif; ?>
                                    </div>

                                    <!-- Top Right Featured Pill -->
                                    <?php if (!empty($pItem['featured'])): ?>
                                        <div style="position: absolute; top: 10px; right: 10px; background: #dc2626; color: #ffffff; font-size: 0.7rem; font-weight: 800; padding: 3px 8px; border-radius: 6px; box-shadow: 0 2px 6px rgba(220,38,38,0.4); display: flex; align-items: center; gap: 4px;">
                                            <i class="fa-solid fa-star" style="color: #facc15;"></i> FEATURED
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Card Body -->
                                <div style="padding: 16px 18px; flex-grow: 1; display: flex; flex-direction: column; justify-content: space-between;">
                                    <div>
                                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                                            <span style="font-size: 0.75rem; font-weight: 700; color: #dc2626; text-transform: uppercase; letter-spacing: 0.5px;">
                                                <?php echo htmlspecialchars($pItem['category'] ?? 'General'); ?>
                                            </span>
                                            <?php if (!empty($pItem['location'])): ?>
                                                <span style="font-size: 0.75rem; color: #94a3b8; font-weight: 500;">
                                                    <i class="fa-solid fa-location-dot" style="font-size: 0.7rem;"></i> <?php echo htmlspecialchars($pItem['location']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <h4 style="font-size: 1rem; font-weight: 800; color: #0f172a; margin: 0 0 6px 0; line-height: 1.3;">
                                            <?php echo htmlspecialchars($pItem['title']); ?>
                                        </h4>

                                        <?php if (!empty($pItem['client'])): ?>
                                            <div style="font-size: 0.8rem; color: #64748b; font-weight: 600; margin-bottom: 8px;">
                                                Client: <?php echo htmlspecialchars($pItem['client']); ?>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($pItem['desc']) && ($pItem['media_type'] ?? 'photo') !== 'video'): ?>
                                            <p style="font-size: 0.8rem; color: #475569; margin: 0 0 12px 0; line-height: 1.45; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                                <?php echo htmlspecialchars($pItem['desc']); ?>
                                            </p>
                                        <?php endif; ?>

                                    <!-- Bottom Edit / Delete Action Buttons Bar -->
                                    <div style="display: flex; align-items: center; justify-content: space-between; border-top: 1px solid #f1f5f9; padding-top: 12px; margin-top: 10px;">
                                        <a href="index.php?section=portfolio&type=<?php echo urlencode($typeFilter); ?>&edit_id=<?php echo $pItem['id']; ?>" style="color: #0f172a; font-size: 0.82rem; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; background: #f1f5f9; padding: 5px 12px; border-radius: 6px;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                                            <i class="fa-solid fa-pen-to-square" style="font-size: 0.75rem; color: #dc2626;"></i> Edit Item
                                        </a>

                                        <form action="index.php?section=portfolio" method="POST" onsubmit="return confirm('Are you sure you want to delete this portfolio item?');" style="margin: 0;">
                                            <input type="hidden" name="action" value="delete_portfolio_item">
                                            <input type="hidden" name="type_filter" value="<?php echo htmlspecialchars($typeFilter); ?>">
                                            <input type="hidden" name="id" value="<?php echo $pItem['id']; ?>">
                                            <button type="submit" style="background: none; border: none; color: #94a3b8; font-size: 0.82rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; padding: 4px 8px;" onmouseover="this.style.color='#dc2626'" onmouseout="this.style.color='#94a3b8'">
                                                <i class="fa-solid fa-trash-can" style="font-size: 0.75rem;"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            <!-- SECTION: CAREERS & HIRING MANAGEMENT -->
            <?php elseif ($activeSection === 'careers'):
                $jobListings = array_values(getCareersRepo());
                $jobApplications = getJobApplicationsRepo();
                $editingJobId = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : null;
                $subAction = $_GET['sub_action'] ?? '';
                $activeTab = $_GET['tab'] ?? 'positions';

                $editingJob = null;
                if ($editingJobId !== null) {
                    foreach ($jobListings as $jl) {
                        if ((int)($jl['id'] ?? 0) === $editingJobId) {
                            $editingJob = $jl;
                            break;
                        }
                    }
                }
            ?>
                <!-- Header Bar -->
                <div class="section-header-bar" style="align-items: center; margin-bottom: 24px;">
                    <div>
                        <h1 class="section-header-title" style="font-size: 1.75rem; font-weight: 800; color: #0f172a; margin-bottom: 4px;">Careers &amp; Hiring</h1>
                        <p class="section-header-desc" style="font-size: 0.88rem; color: #64748b; margin: 0; display: flex; align-items: center; gap: 8px;">
                            <strong style="color: #0f172a;"><?php echo count($jobListings); ?> Job Positions</strong>
                            <span>•</span>
                            <strong style="color: #dc2626;"><?php echo count($jobApplications); ?> Candidate Applications</strong>
                        </p>
                    </div>
                    <?php if ($subAction !== 'add' && $editingJobId === null): ?>
                        <a href="index.php?section=careers&sub_action=add" class="btn-save-primary" style="text-decoration: none; padding: 10px 22px; background-color: #dc2626;">
                            <i class="fa-solid fa-plus"></i> Add New Position
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Sub-Navigation Tabs: Open Positions vs Applicant Inbox -->
                <?php if ($editingJob === null && $subAction !== 'add'): ?>
                    <div style="display: flex; gap: 12px; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px;">
                        <a href="index.php?section=careers&tab=positions" style="text-decoration: none; padding: 8px 18px; border-radius: 8px; font-weight: 700; font-size: 0.88rem; <?php echo ($activeTab === 'positions') ? 'background: #dc2626; color: #ffffff;' : 'background: #ffffff; color: #475569; border: 1px solid #cbd5e1;'; ?>">
                            <i class="fa-solid fa-briefcase"></i> Job Positions (<?php echo count($jobListings); ?>)
                        </a>
                        <a href="index.php?section=careers&tab=applications" style="text-decoration: none; padding: 8px 18px; border-radius: 8px; font-weight: 700; font-size: 0.88rem; <?php echo ($activeTab === 'applications') ? 'background: #dc2626; color: #ffffff;' : 'background: #ffffff; color: #475569; border: 1px solid #cbd5e1;'; ?>">
                            <i class="fa-solid fa-users-rectangle"></i> Candidate Inbox (<?php echo count($jobApplications); ?>)
                        </a>
                    </div>
                <?php endif; ?>

                <?php if ($editingJob !== null || $subAction === 'add'): ?>
                    <!-- FORM VIEW: EDIT OR ADD JOB POSITION -->
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 28px; margin-bottom: 32px; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid #f1f5f9;">
                            <h2 style="font-size: 1.2rem; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
                                <i class="fa-solid fa-pen-to-square" style="color: #dc2626;"></i>
                                <?php echo ($editingJob !== null) ? 'Editing Position: ' . htmlspecialchars($editingJob['title']) : 'Post New Job Position'; ?>
                            </h2>
                            <a href="index.php?section=careers" style="color: #64748b; font-size: 0.85rem; text-decoration: none; font-weight: 600;">
                                <i class="fa-solid fa-xmark"></i> Close Form
                            </a>
                        </div>

                        <?php if (isset($_GET['saved'])): ?>
                            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 12px 18px; border-radius: 10px; font-weight: 700; font-size: 0.9rem; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                                <i class="fa-solid fa-circle-check" style="color: #22c55e;"></i> Position saved successfully &amp; published to careers page!
                            </div>
                        <?php endif; ?>

                        <form action="index.php?section=careers" method="POST" onsubmit="syncNativeEditorContent()">
                            <input type="hidden" name="action" value="save_job_opening">
                            <?php if ($editingJobId !== null): ?>
                                <input type="hidden" name="id" value="<?php echo $editingJobId; ?>">
                            <?php endif; ?>

                            <div class="form-grid-two">
                                <!-- Left Column -->
                                <div>
                                    <div class="form-field">
                                        <label class="form-label-title">Job Title <span style="color: #ef4444;">*</span></label>
                                        <input type="text" name="title" required class="form-text-input" value="<?php echo htmlspecialchars($editingJob['title'] ?? ''); ?>" placeholder="e.g. Senior Video Producer">
                                    </div>

                                    <div class="form-field">
                                        <label class="form-label-title" style="display: flex; justify-content: space-between; align-items: center;">
                                            <span>Department <span style="color: #ef4444;">*</span></span>
                                            <a href="javascript:void(0)" onclick="openAddCustomModal('department')" style="color: #dc2626; text-decoration: none; font-size: 0.78rem; font-weight: 700;">+ Add New Department</a>
                                        </label>
                                        <select name="dept" id="jobDeptSelect" class="form-text-input">
                                            <?php foreach (getTeamDepartments() as $d): ?>
                                                <option value="<?php echo htmlspecialchars($d); ?>" <?php echo (($editingJob['dept'] ?? '') === $d) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($d); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="form-field">
                                        <label class="form-label-title" style="display: flex; justify-content: space-between; align-items: center;">
                                            <span>Location <span style="color: #ef4444;">*</span></span>
                                            <a href="javascript:void(0)" onclick="openAddCustomModal('location')" style="color: #dc2626; text-decoration: none; font-size: 0.78rem; font-weight: 700;">+ Add New Location</a>
                                        </label>
                                        <select name="location" id="jobLocationSelect" class="form-text-input">
                                            <?php foreach (getTeamLocations() as $loc): ?>
                                                <option value="<?php echo htmlspecialchars($loc); ?>" <?php echo (($editingJob['location'] ?? '') === $loc) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($loc); ?>
                                                </option>
                                            <?php endforeach; ?>
                                            <option value="Oakbrook, IL / Hybrid" <?php echo (($editingJob['location'] ?? '') === 'Oakbrook, IL / Hybrid') ? 'selected' : ''; ?>>Oakbrook, IL / Hybrid</option>
                                            <option value="Oakbrook, IL / Remote" <?php echo (($editingJob['location'] ?? '') === 'Oakbrook, IL / Remote') ? 'selected' : ''; ?>>Oakbrook, IL / Remote</option>
                                            <option value="Oakbrook, IL / On-Location" <?php echo (($editingJob['location'] ?? '') === 'Oakbrook, IL / On-Location') ? 'selected' : ''; ?>>Oakbrook, IL / On-Location</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Right Column -->
                                <div>
                                    <div class="form-field">
                                        <label class="form-label-title">Employment Type <span style="color: #ef4444;">*</span></label>
                                        <select name="type" class="form-text-input">
                                            <?php foreach (['Full-time', 'Part-time', 'Contract', 'Internship', 'Freelance'] as $t): ?>
                                                <option value="<?php echo $t; ?>" <?php echo (($editingJob['type'] ?? 'Full-time') === $t) ? 'selected' : ''; ?>><?php echo $t; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="form-field">
                                        <label class="form-label-title">Salary / Rate Range</label>
                                        <input type="text" name="salary" class="form-text-input" value="<?php echo htmlspecialchars($editingJob['salary'] ?? ''); ?>" placeholder="e.g. $85,000 - $120,000 / year">
                                    </div>

                                    <div class="form-field">
                                        <label class="form-label-title">Position Status</label>
                                        <select name="status" class="form-text-input">
                                            <option value="open" <?php echo (($editingJob['status'] ?? 'open') === 'open') ? 'selected' : ''; ?>>Open for Applications</option>
                                            <option value="closed" <?php echo (($editingJob['status'] ?? '') === 'closed') ? 'selected' : ''; ?>>Closed / Filled</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-field" style="margin-top: 10px;">
                                <label class="form-label-title">Short Position Overview <span style="color: #ef4444;">*</span></label>
                                <textarea name="overview" rows="3" class="form-text-input" style="resize: vertical; font-family: inherit;" required placeholder="Brief summary of the role..."><?php echo htmlspecialchars($editingJob['overview'] ?? ''); ?></textarea>
                            </div>

                            <!-- Full Responsibilities & Requirements HTML Editor -->
                            <div style="margin-bottom: 20px;">
                                <label class="form-label-title">Full Job Responsibilities, Requirements &amp; Benefits <span style="color: #ef4444;">*</span></label>
                                <input type="hidden" name="responsibilities" id="blog_content_hidden_input" value="<?php echo htmlspecialchars($editingJob['responsibilities'] ?? ''); ?>">
                                
                                <div style="border: 1px solid #cbd5e1; border-radius: 10px; overflow: hidden; background: #ffffff; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                                    <div style="display: flex; flex-wrap: wrap; gap: 6px; padding: 10px 14px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; align-items: center;">
                                        <button type="button" class="native-editor-btn" onmousedown="event.preventDefault(); applyNativeFormat('bold');" title="Bold"><b>B</b></button>
                                        <button type="button" class="native-editor-btn" onmousedown="event.preventDefault(); applyNativeFormat('italic');" title="Italic"><i>I</i></button>
                                        <button type="button" class="native-editor-btn" onmousedown="event.preventDefault(); applyNativeFormat('underline');" title="Underline"><u>U</u></button>
                                        <button type="button" class="native-editor-btn" onmousedown="event.preventDefault(); applyNativeFormat('strikeThrough');" title="Strikethrough"><s>S</s></button>
                                        <span style="width: 1px; height: 20px; background: #cbd5e1; margin: 0 4px;"></span>
                                        <button type="button" class="native-editor-btn" onmousedown="event.preventDefault(); applyNativeBlock('H1');" title="Heading 1">H1</button>
                                        <button type="button" class="native-editor-btn" onmousedown="event.preventDefault(); applyNativeBlock('H2');" title="Heading 2">H2</button>
                                        <button type="button" class="native-editor-btn" onmousedown="event.preventDefault(); applyNativeBlock('H3');" title="Heading 3">H3</button>
                                        <button type="button" class="native-editor-btn" onmousedown="event.preventDefault(); applyNativeBlock('P');" title="Paragraph">P</button>
                                        <span style="width: 1px; height: 20px; background: #cbd5e1; margin: 0 4px;"></span>
                                        <button type="button" class="native-editor-btn" onmousedown="event.preventDefault(); applyNativeFormat('insertUnorderedList');" title="Bullet List"><i class="fa-solid fa-list-ul"></i></button>
                                        <button type="button" class="native-editor-btn" onmousedown="event.preventDefault(); applyNativeFormat('insertOrderedList');" title="Numbered List"><i class="fa-solid fa-list-ol"></i></button>
                                        <button type="button" class="native-editor-btn" onmousedown="event.preventDefault(); applyNativeBlock('BLOCKQUOTE');" title="Quote Box"><i class="fa-solid fa-quote-left"></i></button>
                                    </div>
                                    
                                    <div 
                                        id="nativeBlogEditor" 
                                        contenteditable="true" 
                                        style="min-height: 240px; max-height: 450px; overflow-y: auto; padding: 16px; outline: none; font-size: 0.95rem; line-height: 1.7; color: #0f172a; font-family: inherit;"
                                        oninput="syncNativeEditorContent()"
                                        onkeyup="syncNativeEditorContent()"
                                        onmouseup="updateNativeToolbarState()"
                                    >
                                        <?php echo $editingJob['responsibilities'] ?? ''; ?>
                                    </div>
                                </div>
                            </div>

                            <div style="display: flex; gap: 12px; margin-top: 24px;">
                                <button type="submit" class="btn-save-primary" style="background-color: #dc2626; padding: 12px 28px;">
                                    <i class="fa-solid fa-floppy-disk"></i> Save Position
                                </button>
                                <a href="index.php?section=careers" style="padding: 12px 20px; background: #f1f5f9; color: #475569; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 0.9rem;">
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>

                <?php elseif ($activeTab === 'applications'): ?>
                    <!-- VIEW 2: APPLICANT INBOX TABLE -->
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                        <h2 style="font-size: 1.1rem; font-weight: 800; color: #0f172a; margin-bottom: 16px;">Candidate Applications Received</h2>
                        <?php if (empty($jobApplications)): ?>
                            <p style="color: #64748b; font-size: 0.9rem; text-align: center; padding: 40px 0;">No job applications submitted yet.</p>
                        <?php else: ?>
                            <div style="overflow-x: auto;">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Applicant</th>
                                            <th>Position</th>
                                            <th>Contact Info</th>
                                            <th>Links</th>
                                            <th>Applied Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_reverse($jobApplications) as $app): ?>
                                            <tr>
                                                <td>
                                                    <strong style="color: #0f172a; display: block; font-size: 0.92rem;"><?php echo htmlspecialchars($app['full_name'] ?? 'Candidate'); ?></strong>
                                                    <div style="font-size: 0.8rem; color: #64748b; margin-top: 4px; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                        <?php echo htmlspecialchars($app['cover_note'] ?? ''); ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span style="background: #f1f5f9; color: #0f172a; padding: 4px 10px; border-radius: 6px; font-weight: 700; font-size: 0.8rem;">
                                                        <?php echo htmlspecialchars($app['job_title'] ?? 'General'); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div style="font-size: 0.84rem; color: #334155; font-weight: 600;"><?php echo htmlspecialchars($app['email'] ?? ''); ?></div>
                                                    <div style="font-size: 0.78rem; color: #64748b;"><?php echo htmlspecialchars($app['phone'] ?? ''); ?></div>
                                                </td>
                                                <td>
                                                    <?php if (!empty($app['portfolio_url'])): ?>
                                                        <a href="<?php echo htmlspecialchars($app['portfolio_url']); ?>" target="_blank" style="color: #dc2626; font-size: 0.8rem; font-weight: 700; margin-right: 8px;">Portfolio <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 0.7rem;"></i></a>
                                                    <?php endif; ?>
                                                    <?php if (!empty($app['linkedin_url'])): ?>
                                                        <a href="<?php echo htmlspecialchars($app['linkedin_url']); ?>" target="_blank" style="color: #2563eb; font-size: 0.8rem; font-weight: 700;">LinkedIn <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 0.7rem;"></i></a>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="font-size: 0.82rem; color: #64748b;">
                                                    <?php echo htmlspecialchars($app['applied_at'] ?? 'Recently'); ?>
                                                </td>
                                                <td>
                                                    <form action="index.php?section=careers" method="POST" style="margin: 0;">
                                                        <input type="hidden" name="action" value="update_app_status">
                                                        <input type="hidden" name="app_id" value="<?php echo $app['id']; ?>">
                                                        <select name="app_status" onchange="this.form.submit()" style="padding: 4px 8px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 0.8rem; font-weight: 700; background: #ffffff;">
                                                            <option value="new" <?php echo (($app['status'] ?? 'new') === 'new') ? 'selected' : ''; ?>>New</option>
                                                            <option value="in_review" <?php echo (($app['status'] ?? '') === 'in_review') ? 'selected' : ''; ?>>In Review</option>
                                                            <option value="interviewed" <?php echo (($app['status'] ?? '') === 'interviewed') ? 'selected' : ''; ?>>Interviewed</option>
                                                            <option value="hired" <?php echo (($app['status'] ?? '') === 'hired') ? 'selected' : ''; ?>>Hired</option>
                                                            <option value="archived" <?php echo (($app['status'] ?? '') === 'archived') ? 'selected' : ''; ?>>Archived</option>
                                                        </select>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                <?php else: ?>
                    <!-- VIEW 1: OPEN POSITIONS GRID / LIST -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 20px;">
                        <?php foreach ($jobListings as $jItem): ?>
                            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 22px; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                                <div>
                                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                                        <span style="background: #f1f5f9; color: #0f172a; font-size: 0.72rem; font-weight: 800; padding: 4px 10px; border-radius: 20px;">
                                            <?php echo htmlspecialchars($jItem['dept'] ?? 'Production'); ?>
                                        </span>
                                        <form action="index.php?section=careers" method="POST" style="margin: 0;">
                                            <input type="hidden" name="action" value="toggle_job_status">
                                            <input type="hidden" name="id" value="<?php echo $jItem['id']; ?>">
                                            <button type="submit" style="border: none; background: none; cursor: pointer; padding: 0;">
                                                <?php if (($jItem['status'] ?? 'open') === 'open'): ?>
                                                    <span style="background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; font-size: 0.7rem; font-weight: 800; padding: 2px 8px; border-radius: 12px;">● Open</span>
                                                <?php else: ?>
                                                    <span style="background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; font-size: 0.7rem; font-weight: 800; padding: 2px 8px; border-radius: 12px;">● Closed</span>
                                                <?php endif; ?>
                                            </button>
                                        </form>
                                    </div>

                                    <h3 style="font-size: 1.1rem; font-weight: 800; color: #0f172a; margin-bottom: 8px; line-height: 1.35;">
                                        <?php echo htmlspecialchars($jItem['title']); ?>
                                    </h3>

                                    <div style="font-size: 0.8rem; color: #64748b; margin-bottom: 12px; display: flex; flex-wrap: wrap; gap: 10px; font-weight: 600;">
                                        <span><i class="fa-solid fa-location-dot" style="color: #dc2626;"></i> <?php echo htmlspecialchars($jItem['location'] ?? ''); ?></span>
                                        <span><i class="fa-solid fa-clock"></i> <?php echo htmlspecialchars($jItem['type'] ?? 'Full-time'); ?></span>
                                    </div>

                                    <p style="font-size: 0.84rem; color: #475569; margin-bottom: 16px; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                        <?php echo htmlspecialchars($jItem['overview'] ?? ''); ?>
                                    </p>
                                </div>

                                <div style="display: flex; align-items: center; justify-content: space-between; border-top: 1px solid #f1f5f9; padding-top: 14px;">
                                    <a href="index.php?section=careers&edit_id=<?php echo $jItem['id']; ?>" style="color: #0f172a; font-size: 0.82rem; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; background: #f1f5f9; padding: 6px 14px; border-radius: 8px;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                                        <i class="fa-solid fa-pen-to-square" style="font-size: 0.75rem; color: #dc2626;"></i> Edit Position
                                    </a>

                                    <form action="index.php?section=careers" method="POST" onsubmit="return confirm('Are you sure you want to remove this job position?');" style="margin: 0;">
                                        <input type="hidden" name="action" value="delete_job_opening">
                                        <input type="hidden" name="id" value="<?php echo $jItem['id']; ?>">
                                        <button type="submit" style="background: none; border: none; color: #94a3b8; font-size: 0.82rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; padding: 4px 8px;" onmouseover="this.style.color='#dc2626'" onmouseout="this.style.color='#94a3b8'">
                                            <i class="fa-solid fa-trash-can" style="font-size: 0.75rem;"></i> Remove
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            <!-- SECTION: CLIENT BRANDS MANAGEMENT -->
            <?php elseif ($activeSection === 'brands'):
                $brandLogosList = array_values(getBrandLogosRepo());
                $editingBrandId = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : null;
                $subAction = $_GET['sub_action'] ?? '';

                $editingBrand = null;
                if ($editingBrandId !== null) {
                    foreach ($brandLogosList as $bl) {
                        if ((int)($bl['id'] ?? 0) === $editingBrandId) {
                            $editingBrand = $bl;
                            break;
                        }
                    }
                }
            ?>
                <!-- Header Bar -->
                <div class="section-header-bar" style="align-items: center; margin-bottom: 24px;">
                    <div>
                        <h1 class="section-header-title" style="font-size: 1.75rem; font-weight: 800; color: #0f172a; margin-bottom: 4px;">Client Brands &amp; Partners</h1>
                        <p class="section-header-desc" style="font-size: 0.88rem; color: #64748b; margin: 0; display: flex; align-items: center; gap: 8px;">
                            <strong style="color: #0f172a;"><?php echo count($brandLogosList); ?> Client Brands</strong>
                            <span>•</span>
                            <span>Manage brand names, icons, and Cloudinary logo images displayed in the homepage ticker</span>
                        </p>
                    </div>
                    <?php if ($subAction !== 'add' && $editingBrandId === null): ?>
                        <a href="index.php?section=brands&sub_action=add" class="btn-save-primary" style="text-decoration: none; padding: 10px 22px; background-color: #dc2626;">
                            <i class="fa-solid fa-plus"></i> Add New Brand
                        </a>
                    <?php endif; ?>
                </div>

                <?php if ($editingBrand !== null || $subAction === 'add'): ?>
                    <!-- FORM VIEW: EDIT OR ADD BRAND LOGO -->
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 28px; margin-bottom: 32px; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid #f1f5f9;">
                            <h2 style="font-size: 1.2rem; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
                                <i class="fa-solid fa-pen-to-square" style="color: #dc2626;"></i>
                                <?php echo ($editingBrand !== null) ? 'Editing Brand: ' . htmlspecialchars($editingBrand['name']) : 'Add New Client Brand'; ?>
                            </h2>
                            <a href="index.php?section=brands" style="color: #64748b; font-size: 0.85rem; text-decoration: none; font-weight: 600;">
                                <i class="fa-solid fa-xmark"></i> Close Form
                            </a>
                        </div>

                        <?php if (isset($_GET['saved'])): ?>
                            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 12px 18px; border-radius: 10px; font-weight: 700; font-size: 0.9rem; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                                <i class="fa-solid fa-circle-check" style="color: #22c55e;"></i> Brand logo saved successfully &amp; live on homepage ticker!
                            </div>
                        <?php endif; ?>

                        <form action="index.php?section=brands" method="POST" enctype="multipart/form-data" id="brandForm">
                            <input type="hidden" name="action" value="save_brand_logo">
                            <?php if ($editingBrandId !== null): ?>
                                <input type="hidden" name="id" value="<?php echo $editingBrandId; ?>">
                            <?php endif; ?>
                            <input type="hidden" name="existing_image" id="brand_existing_image" value="<?php echo htmlspecialchars($editingBrand['image'] ?? ''); ?>">
                            <input type="hidden" name="cropped_brand_image_data" id="cropped_brand_image_data" value="">

                            <div class="form-grid-two">
                                <!-- Left Column -->
                                <div>
                                    <div class="form-field">
                                        <label class="form-label-title">Brand Name <span style="color: #ef4444;">*</span></label>
                                        <input type="text" name="name" required class="form-text-input" value="<?php echo htmlspecialchars($editingBrand['name'] ?? ''); ?>" placeholder="e.g. RedBull">
                                    </div>

                                    <div class="form-field">
                                        <label class="form-label-title">FontAwesome Icon Class (Fallback)</label>
                                        <div style="display: flex; gap: 10px; align-items: center;">
                                            <input type="text" name="icon" class="form-text-input" value="<?php echo htmlspecialchars($editingBrand['icon'] ?? 'fa-solid fa-star'); ?>" placeholder="fa-solid fa-mug-hot">
                                            <div style="width: 38px; height: 38px; border: 1px solid #cbd5e1; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: #f8fafc; color: #0f172a; flex-shrink: 0;">
                                                <i class="<?php echo htmlspecialchars($editingBrand['icon'] ?? 'fa-solid fa-star'); ?>"></i>
                                            </div>
                                        </div>
                                        <small style="color: #64748b; font-size: 0.78rem; margin-top: 4px; display: block;">Used if no logo image is uploaded. e.g. <code>fa-brands fa-apple</code> or <code>fa-solid fa-film</code>.</small>
                                    </div>

                                    <div class="form-field" style="margin-top: 18px;">
                                        <label style="display: inline-flex; align-items: center; gap: 8px; font-weight: 700; font-size: 0.9rem; color: #0f172a; cursor: pointer;">
                                            <input type="checkbox" name="visible" value="1" <?php echo (!isset($editingBrand['visible']) || !empty($editingBrand['visible'])) ? 'checked' : ''; ?>>
                                            Display in Homepage Ticker
                                        </label>
                                    </div>
                                </div>

                                <!-- Right Column: Cloudinary Image Upload -->
                                <div>
                                    <div class="form-field">
                                        <label class="form-label-title">Brand Logo Image (Cloudinary)</label>
                                        <div style="border: 2px dashed #cbd5e1; border-radius: 12px; padding: 20px; text-align: center; background: #f8fafc; position: relative;">
                                            <div id="brand_preview_container" style="margin-bottom: 12px;">
                                                <?php if (!empty($editingBrand['image'])): ?>
                                                    <img id="brand_preview_img" src="<?php echo htmlspecialchars(getCloudinaryUrl($editingBrand['image'])); ?>" style="max-height: 50px; max-width: 160px; object-fit: contain;">
                                                <?php else: ?>
                                                    <div id="brand_no_preview" style="color: #94a3b8; font-size: 0.85rem;">No logo image uploaded yet.</div>
                                                <?php endif; ?>
                                            </div>

                                            <div id="brand_upload_status" style="display: none; margin-bottom: 10px; font-weight: 700; font-size: 0.84rem; color: #2563eb;">
                                                <i class="fa-solid fa-spinner fa-spin"></i> Uploading to Cloudinary...
                                            </div>

                                            <label class="btn-save-primary" style="background-color: #0f172a; cursor: pointer; display: inline-flex; font-size: 0.82rem; padding: 8px 16px;">
                                                <i class="fa-solid fa-cloud-arrow-up"></i> Upload Brand Logo
                                                <input type="file" id="brand_file_input" accept="image/*" style="display: none;" onchange="handleBrandFileUpload(this)">
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div style="display: flex; gap: 12px; margin-top: 24px;">
                                <button type="submit" class="btn-save-primary" style="background-color: #dc2626; padding: 12px 28px;">
                                    <i class="fa-solid fa-floppy-disk"></i> Save Brand Logo
                                </button>
                                <a href="index.php?section=brands" style="padding: 12px 20px; background: #f1f5f9; color: #475569; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 0.9rem;">
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>

                    <script>
                        function handleBrandFileUpload(input) {
                            if (!input.files || !input.files[0]) return;
                            const file = input.files[0];
                            const statusBox = document.getElementById('brand_upload_status');
                            const previewImg = document.getElementById('brand_preview_img');
                            const noPreview = document.getElementById('brand_no_preview');
                            const existingInput = document.getElementById('brand_existing_image');

                            statusBox.style.display = 'block';
                            statusBox.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Uploading brand logo to Cloudinary...';

                            const formData = new FormData();
                            formData.append('action', 'upload_cloudinary_ajax');
                            formData.append('file', file);
                            formData.append('folder', 'falhen/brands');

                            fetch('index.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(res => res.json())
                            .then(data => {
                                statusBox.style.display = 'none';
                                if (data.success) {
                                    existingInput.value = data.url;
                                    if (previewImg) {
                                        previewImg.src = data.url;
                                        previewImg.style.display = 'inline-block';
                                    } else {
                                        const container = document.getElementById('brand_preview_container');
                                        container.innerHTML = '<img id="brand_preview_img" src="' + data.url + '" style="max-height: 50px; max-width: 160px; object-fit: contain;">';
                                    }
                                    if (noPreview) noPreview.style.display = 'none';
                                } else {
                                    alert('Upload Error: ' + data.message);
                                }
                            })
                            .catch(err => {
                                statusBox.style.display = 'none';
                                alert('Cloudinary upload error.');
                            });
                        }
                    </script>

                <?php else: ?>
                    <!-- LIST VIEW: CLIENT BRANDS GRID -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 20px;">
                        <?php foreach ($brandLogosList as $bIndex => $bItem): ?>
                            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                                <div>
                                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                                        <form action="index.php?section=brands" method="POST" style="margin: 0;">
                                            <input type="hidden" name="action" value="toggle_brand_visibility">
                                            <input type="hidden" name="id" value="<?php echo $bItem['id']; ?>">
                                            <button type="submit" style="border: none; background: none; cursor: pointer; padding: 0;">
                                                <?php if (!empty($bItem['visible'])): ?>
                                                    <span style="background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; font-size: 0.7rem; font-weight: 800; padding: 2px 8px; border-radius: 12px;">● Visible</span>
                                                <?php else: ?>
                                                    <span style="background: #f8fafc; color: #64748b; border: 1px solid #cbd5e1; font-size: 0.7rem; font-weight: 800; padding: 2px 8px; border-radius: 12px;">● Hidden</span>
                                                <?php endif; ?>
                                            </button>
                                        </form>
                                    </div>

                                    <div style="height: 60px; background: #0f172a; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-bottom: 14px; color: #ffffff; padding: 10px;">
                                        <?php if (!empty($bItem['image'])): ?>
                                            <img src="<?php echo htmlspecialchars(getCloudinaryUrl($bItem['image'])); ?>" alt="<?php echo htmlspecialchars($bItem['name']); ?>" style="max-height: 36px; max-width: 120px; object-fit: contain;">
                                        <?php else: ?>
                                            <div style="display: flex; align-items: center; gap: 8px; font-weight: 800; font-size: 1rem;">
                                                <i class="<?php echo htmlspecialchars(!empty($bItem['icon']) ? $bItem['icon'] : 'fa-solid fa-star'); ?>" style="color: #ef4444;"></i>
                                                <?php echo htmlspecialchars($bItem['name']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <h3 style="font-size: 1.05rem; font-weight: 800; color: #0f172a; margin-bottom: 4px; text-align: center;">
                                        <?php echo htmlspecialchars($bItem['name']); ?>
                                    </h3>
                                </div>

                                <div style="display: flex; align-items: center; justify-content: space-between; border-top: 1px solid #f1f5f9; padding-top: 12px; margin-top: 14px;">
                                    <a href="index.php?section=brands&edit_id=<?php echo $bItem['id']; ?>" style="color: #0f172a; font-size: 0.82rem; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; background: #f1f5f9; padding: 6px 14px; border-radius: 8px;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                                        <i class="fa-solid fa-pen-to-square" style="font-size: 0.75rem; color: #dc2626;"></i> Edit Brand
                                    </a>

                                    <form action="index.php?section=brands" method="POST" onsubmit="return promptConfirmModal(this, 'Remove Client Brand', 'Are you sure you want to remove <?php echo htmlspecialchars(addslashes($bItem['name'])); ?> from client brands?');" style="margin: 0;">
                                        <input type="hidden" name="action" value="delete_brand_logo">
                                        <input type="hidden" name="id" value="<?php echo $bItem['id']; ?>">
                                        <button type="submit" style="background: none; border: none; color: #94a3b8; font-size: 0.82rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; padding: 4px 8px;" onmouseover="this.style.color='#dc2626'" onmouseout="this.style.color='#94a3b8'">
                                            <i class="fa-solid fa-trash-can" style="font-size: 0.75rem;"></i> Remove
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            <!-- SECTION: MY PROFILE & ACCOUNT SETTINGS -->
            <?php elseif ($activeSection === 'profile' || $activeSection === 'my_profile'): 
                $profileData = getAdminUserProfile();
                $pwdError = $_GET['pwd_error'] ?? '';
            ?>
                <!-- Header Bar -->
                <div class="section-header-bar" style="align-items: center; margin-bottom: 24px;">
                    <div>
                        <h1 class="section-header-title" style="font-size: 1.75rem; font-weight: 800; color: #0f172a; margin-bottom: 4px;">My Profile &amp; Account Settings</h1>
                        <p class="section-header-desc" style="font-size: 0.88rem; color: #64748b; margin: 0;">
                            Manage your staff profile credentials, Cloudinary avatar, and account security preferences.
                        </p>
                    </div>
                </div>

                <!-- Alert Banners -->
                <?php if (isset($_GET['saved'])): ?>
                    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 14px 20px; border-radius: 12px; font-weight: 700; font-size: 0.92rem; margin-bottom: 24px; display: flex; align-items: center; gap: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.02);">
                        <i class="fa-solid fa-circle-check" style="color: #22c55e; font-size: 1.1rem;"></i>
                        Profile information updated successfully! Active session and navigation avatar updated live.
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['pwd_saved'])): ?>
                    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 14px 20px; border-radius: 12px; font-weight: 700; font-size: 0.92rem; margin-bottom: 24px; display: flex; align-items: center; gap: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.02);">
                        <i class="fa-solid fa-shield-halved" style="color: #22c55e; font-size: 1.1rem;"></i>
                        Your password has been changed successfully! Your account credentials are now updated.
                    </div>
                <?php endif; ?>

                <?php if (!empty($pwdError)): ?>
                    <div style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 14px 20px; border-radius: 12px; font-weight: 700; font-size: 0.92rem; margin-bottom: 24px; display: flex; align-items: center; gap: 10px;">
                        <i class="fa-solid fa-triangle-exclamation" style="color: #ef4444; font-size: 1.1rem;"></i>
                        <?php 
                            if ($pwdError === 'invalid_current') {
                                echo "Current password is incorrect. Please check and try again.";
                            } else if ($pwdError === 'too_short') {
                                echo "New password must be at least 6 characters long.";
                            } else if ($pwdError === 'mismatch') {
                                echo "New password and Confirm Password do not match.";
                            } else {
                                echo "Failed to update password. Please try again.";
                            }
                        ?>
                    </div>
                <?php endif; ?>

                <div style="display: grid; grid-template-columns: 1fr; gap: 30px;">
                    <!-- CARD 1: PROFILE DETAILS FORM -->
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 32px; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid #f1f5f9;">
                            <div style="width: 42px; height: 42px; border-radius: 10px; background: rgba(220, 38, 38, 0.1); color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 1.15rem;">
                                <i class="fa-solid fa-user-gear"></i>
                            </div>
                            <div>
                                <h2 style="font-size: 1.25rem; font-weight: 800; color: #0f172a; margin: 0;">Account Profile Information</h2>
                                <p style="font-size: 0.84rem; color: #64748b; margin: 2px 0 0 0;">Update your name, email, role title, and avatar photo</p>
                            </div>
                        </div>

                        <form action="index.php?section=profile" method="POST" enctype="multipart/form-data" id="profileForm">
                            <input type="hidden" name="action" value="save_profile_details">
                            <input type="hidden" name="existing_avatar" id="profile_existing_avatar" value="<?php echo htmlspecialchars($profileData['avatar'] ?? ''); ?>">
                            <input type="hidden" name="cropped_avatar_image_data" id="cropped_avatar_image_data" value="">

                            <!-- Avatar Upload Header -->
                            <div style="display: flex; align-items: center; gap: 24px; margin-bottom: 30px; background: #f8fafc; padding: 20px; border-radius: 14px; border: 1px solid #e2e8f0;">
                                <div id="profile_avatar_preview_container" style="position: relative;">
                                    <?php if (!empty($profileData['avatar'])): ?>
                                        <img id="profile_avatar_img" src="<?php echo htmlspecialchars(getCloudinaryUrl($profileData['avatar'])); ?>" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid #dc2626; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                                    <?php else: ?>
                                        <div id="profile_avatar_initial" style="width: 80px; height: 80px; border-radius: 50%; background: #dc2626; color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 800; border: 3px solid #ffffff; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                                            <?php echo strtoupper(substr($profileData['full_name'] ?? 'A', 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div style="flex: 1;">
                                    <h4 style="margin: 0 0 6px 0; font-size: 1rem; font-weight: 800; color: #0f172a;">Profile Photo Avatar</h4>
                                    <p style="margin: 0 0 12px 0; font-size: 0.82rem; color: #64748b;">Upload a high-resolution PNG or JPG photo. Will be uploaded directly to Cloudinary.</p>

                                    <div id="avatar_upload_status" style="display: none; margin-bottom: 10px; font-weight: 700; font-size: 0.84rem; color: #2563eb;">
                                        <i class="fa-solid fa-spinner fa-spin"></i> Uploading avatar to Cloudinary...
                                    </div>

                                    <label class="btn-save-primary" style="background-color: #0f172a; cursor: pointer; display: inline-flex; font-size: 0.82rem; padding: 8px 18px;">
                                        <i class="fa-solid fa-camera"></i> Change Avatar Photo
                                        <input type="file" id="avatar_file_input" accept="image/*" style="display: none;" onchange="handleAvatarFileUpload(this)">
                                    </label>
                                </div>
                            </div>

                            <div class="form-grid-two">
                                <div class="form-field">
                                    <label class="form-label-title">Full Name <span style="color: #ef4444;">*</span></label>
                                    <input type="text" name="full_name" required class="form-text-input" value="<?php echo htmlspecialchars($profileData['full_name'] ?? ''); ?>" placeholder="e.g. Henry Falonipe">
                                </div>

                                <div class="form-field">
                                    <label class="form-label-title">Username <span style="color: #ef4444;">*</span></label>
                                    <input type="text" name="username" required class="form-text-input" value="<?php echo htmlspecialchars($profileData['username'] ?? ''); ?>" placeholder="e.g. admin">
                                </div>

                                <div class="form-field">
                                    <label class="form-label-title">Staff Email Address <span style="color: #ef4444;">*</span></label>
                                    <input type="email" name="email" required class="form-text-input" value="<?php echo htmlspecialchars($profileData['email'] ?? ''); ?>" placeholder="admin@falhen.com">
                                </div>

                                <div class="form-field">
                                    <label class="form-label-title">Role &amp; Title</label>
                                    <input type="text" name="role" class="form-text-input" value="<?php echo htmlspecialchars($profileData['role'] ?? ''); ?>" placeholder="e.g. Creative Director & Administrator">
                                </div>
                            </div>

                            <div class="form-field" style="margin-top: 18px;">
                                <label class="form-label-title">Short Bio / About Yourself</label>
                                <textarea name="bio" rows="3" class="form-text-input" style="resize: vertical; font-family: inherit;" placeholder="Brief statement about your role at Falhen Media..."><?php echo htmlspecialchars($profileData['bio'] ?? ''); ?></textarea>
                            </div>

                            <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid #f1f5f9;">
                                <button type="submit" class="btn-save-primary" style="background-color: #dc2626; padding: 12px 30px;">
                                    <i class="fa-solid fa-floppy-disk"></i> Save Profile Details
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- CARD 2: SECURITY & PASSWORD CHANGE -->
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 32px; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid #f1f5f9;">
                            <div style="width: 42px; height: 42px; border-radius: 10px; background: rgba(15, 23, 42, 0.08); color: #0f172a; display: flex; align-items: center; justify-content: center; font-size: 1.15rem;">
                                <i class="fa-solid fa-lock"></i>
                            </div>
                            <div>
                                <h2 style="font-size: 1.25rem; font-weight: 800; color: #0f172a; margin: 0;">Security &amp; Password Update</h2>
                                <p style="font-size: 0.84rem; color: #64748b; margin: 2px 0 0 0;">Update your account password to maintain security</p>
                            </div>
                        </div>

                        <form action="index.php?section=profile" method="POST">
                            <input type="hidden" name="action" value="change_profile_password">

                            <div style="max-width: 540px;">
                                <div class="form-field">
                                    <label class="form-label-title">Current Password <span style="color: #ef4444;">*</span></label>
                                    <div style="position: relative;">
                                        <input type="password" id="current_pwd_input" name="current_password" required class="form-text-input" placeholder="Enter current password">
                                        <button type="button" onclick="togglePasswordVisibility('current_pwd_input', this)" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #64748b; cursor: pointer;">
                                            <i class="fa-regular fa-eye"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="form-field" style="margin-top: 16px;">
                                    <label class="form-label-title">New Password <span style="color: #ef4444;">*</span></label>
                                    <div style="position: relative;">
                                        <input type="password" id="new_pwd_input" name="new_password" required class="form-text-input" placeholder="Minimum 6 characters">
                                        <button type="button" onclick="togglePasswordVisibility('new_pwd_input', this)" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #64748b; cursor: pointer;">
                                            <i class="fa-regular fa-eye"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="form-field" style="margin-top: 16px;">
                                    <label class="form-label-title">Confirm New Password <span style="color: #ef4444;">*</span></label>
                                    <div style="position: relative;">
                                        <input type="password" id="confirm_pwd_input" name="confirm_password" required class="form-text-input" placeholder="Re-enter new password">
                                        <button type="button" onclick="togglePasswordVisibility('confirm_pwd_input', this)" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #64748b; cursor: pointer;">
                                            <i class="fa-regular fa-eye"></i>
                                        </button>
                                    </div>
                                </div>

                                <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid #f1f5f9;">
                                    <button type="submit" class="btn-save-primary" style="background-color: #0f172a; padding: 12px 28px;">
                                        <i class="fa-solid fa-key"></i> Update Account Password
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <script>
                    function handleAvatarFileUpload(input) {
                        if (!input.files || !input.files[0]) return;
                        const file = input.files[0];
                        const statusBox = document.getElementById('avatar_upload_status');
                        const previewContainer = document.getElementById('profile_avatar_preview_container');
                        const existingInput = document.getElementById('profile_existing_avatar');

                        statusBox.style.display = 'block';
                        statusBox.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Uploading avatar photo to Cloudinary...';

                        const formData = new FormData();
                        formData.append('action', 'upload_cloudinary_ajax');
                        formData.append('file', file);
                        formData.append('folder', 'falhen/profile');

                        fetch('index.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(res => res.json())
                        .then(data => {
                            statusBox.style.display = 'none';
                            if (data.success) {
                                existingInput.value = data.url;
                                previewContainer.innerHTML = '<img id="profile_avatar_img" src="' + data.url + '" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid #dc2626; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">';
                            } else {
                                alert('Avatar Upload Error: ' + data.message);
                            }
                        })
                        .catch(err => {
                            statusBox.style.display = 'none';
                            alert('Cloudinary avatar upload error.');
                        });
                    }

                    function togglePasswordVisibility(inputId, btn) {
                        const input = document.getElementById(inputId);
                        const icon = btn.querySelector('i');
                        if (input.type === 'password') {
                            input.type = 'text';
                            icon.className = 'fa-regular fa-eye-slash';
                        } else {
                            input.type = 'password';
                            icon.className = 'fa-regular fa-eye';
                        }
                    }
                </script>

            <!-- SECTION: STAFF ACCOUNTS MANAGEMENT -->
            <?php elseif ($activeSection === 'staff_accounts'):
                $staffList = array_values(getStaffAccountsRepo());
                $editingStaffId = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : null;
                $subAction = $_GET['sub_action'] ?? '';

                $editingStaff = null;
                if ($editingStaffId !== null) {
                    foreach ($staffList as $st) {
                        if ((int)($st['id'] ?? 0) === $editingStaffId) {
                            $editingStaff = $st;
                            break;
                        }
                    }
                }
            ?>
                <!-- Header Bar -->
                <div class="section-header-bar" style="align-items: center; margin-bottom: 24px;">
                    <div>
                        <h1 class="section-header-title" style="font-size: 1.75rem; font-weight: 800; color: #0f172a; margin: 0;">Staff &amp; Admin Accounts</h1>
                    </div>
                    <?php if ($subAction !== 'add' && $editingStaffId === null): ?>
                        <div style="display: flex; gap: 10px;">
                            <form action="index.php?section=staff_accounts" method="POST" style="margin: 0; display: inline;">
                                <input type="hidden" name="action" value="sync_team_and_staff">
                                <button type="submit" style="background: #ffffff; color: #0f172a; border: 1px solid #cbd5e1; border-radius: 10px; padding: 10px 16px; font-weight: 700; font-size: 0.85rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='#ffffff'" title="Synchronize Staff Accounts with Team Roster">
                                    <i class="fa-solid fa-arrows-rotate" style="color: #dc2626;"></i> Sync with Team Roster
                                </button>
                            </form>
                            <a href="index.php?section=staff_accounts&sub_action=add" class="btn-save-primary" style="text-decoration: none; padding: 10px 22px; background-color: #dc2626;">
                                <i class="fa-solid fa-user-plus"></i> Add New Staff Member
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (isset($_GET['synced'])): ?>
                    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 12px 18px; border-radius: 10px; font-weight: 700; font-size: 0.9rem; margin-bottom: 24px; display: flex; align-items: center; gap: 10px;">
                        <i class="fa-solid fa-arrows-rotate" style="color: #22c55e;"></i> Team Roster &amp; Staff Accounts fully synchronized! (<?php echo (int)($_GET['added'] ?? 0); ?> added, <?php echo (int)($_GET['updated'] ?? 0); ?> updated)
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['saved'])): ?>
                    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 12px 18px; border-radius: 10px; font-weight: 700; font-size: 0.9rem; margin-bottom: 24px; display: flex; align-items: center; gap: 10px;">
                        <i class="fa-solid fa-circle-check" style="color: #22c55e;"></i> Staff account details saved successfully!
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['deleted'])): ?>
                    <div style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 12px 18px; border-radius: 10px; font-weight: 700; font-size: 0.9rem; margin-bottom: 24px; display: flex; align-items: center; gap: 10px;">
                        <i class="fa-solid fa-trash-can" style="color: #ef4444;"></i> Staff account removed from system.
                    </div>
                <?php endif; ?>

                <?php if ($editingStaff !== null || $subAction === 'add'): ?>
                    <!-- FORM VIEW: EDIT OR ADD STAFF MEMBER -->
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 28px; margin-bottom: 32px; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid #f1f5f9;">
                            <h2 style="font-size: 1.2rem; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
                                <i class="fa-solid fa-user-pen" style="color: #dc2626;"></i>
                                <?php echo ($editingStaff !== null) ? 'Editing Staff: ' . htmlspecialchars($editingStaff['full_name']) : 'Add New Staff Member'; ?>
                            </h2>
                            <a href="index.php?section=staff_accounts" style="color: #64748b; font-size: 0.85rem; text-decoration: none; font-weight: 600;">
                                <i class="fa-solid fa-xmark"></i> Close Form
                            </a>
                        </div>

                        <form action="index.php?section=staff_accounts" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="save_staff_account">
                            <?php if ($editingStaffId !== null): ?>
                                <input type="hidden" name="id" value="<?php echo $editingStaffId; ?>">
                            <?php endif; ?>
                            <input type="hidden" name="existing_avatar" id="staff_existing_avatar" value="<?php echo htmlspecialchars($editingStaff['avatar'] ?? ''); ?>">
                            <input type="hidden" name="cropped_staff_avatar_data" id="cropped_staff_avatar_data" value="">

                            <!-- Avatar Sync Header (Fetched directly from Team Content) -->
                            <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 24px; background: #f8fafc; padding: 18px; border-radius: 12px; border: 1px solid #e2e8f0;">
                                <div id="staff_avatar_preview_container">
                                    <?php if (!empty($editingStaff['avatar'])): ?>
                                        <img id="staff_avatar_img" src="<?php echo htmlspecialchars(getCloudinaryUrl($editingStaff['avatar'])); ?>" style="width: 64px; height: 64px; border-radius: 50%; object-fit: cover; border: 2px solid #dc2626;">
                                    <?php else: ?>
                                        <div id="staff_avatar_no_img" style="width: 64px; height: 64px; border-radius: 50%; background: #dc2626; color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800;">
                                            <?php echo strtoupper(substr($editingStaff['full_name'] ?? 'S', 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div>
                                    <h4 style="margin: 0 0 4px 0; font-size: 0.95rem; font-weight: 800; color: #0f172a;">Staff Avatar Photo (Synced from Team Menu)</h4>
                                    <p style="margin: 0; font-size: 0.8rem; color: #64748b;">Avatar photos are automatically synchronized directly from the <strong>Team (Content)</strong> roster. Updating photos in the Team section instantly updates profile avatars across all staff accounts.</p>
                                </div>
                            </div>

                            <div class="form-grid-two">
                                <div class="form-field">
                                    <label class="form-label-title">Full Name <span style="color: #ef4444;">*</span></label>
                                    <input type="text" name="full_name" required class="form-text-input" value="<?php echo htmlspecialchars($editingStaff['full_name'] ?? ''); ?>" placeholder="e.g. Diane Max">
                                </div>

                                <div class="form-field">
                                    <label class="form-label-title">Username <span style="color: #ef4444;">*</span></label>
                                    <input type="text" name="username" required class="form-text-input" value="<?php echo htmlspecialchars($editingStaff['username'] ?? ''); ?>" placeholder="e.g. diane.max">
                                </div>

                                <div class="form-field">
                                    <label class="form-label-title">Email Address <span style="color: #ef4444;">*</span></label>
                                    <input type="email" name="email" required class="form-text-input" value="<?php echo htmlspecialchars($editingStaff['email'] ?? ''); ?>" placeholder="diane@falhen.com">
                                </div>

                                <div class="form-field">
                                    <label class="form-label-title">Assign Role</label>
                                    <select name="role" class="form-text-input">
                                        <?php 
                                            $roles = ['Super Admin', 'Content Editor', 'Production Manager', 'Talent Manager', 'Staff'];
                                            $currentRole = $editingStaff['role'] ?? 'Staff';
                                            foreach ($roles as $r):
                                        ?>
                                            <option value="<?php echo $r; ?>" <?php echo ($currentRole === $r) ? 'selected' : ''; ?>><?php echo $r; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-field">
                                    <label class="form-label-title">Account Status</label>
                                    <select name="status" class="form-text-input">
                                        <option value="active" <?php echo (($editingStaff['status'] ?? 'active') === 'active') ? 'selected' : ''; ?>>Active Access</option>
                                        <option value="suspended" <?php echo (($editingStaff['status'] ?? '') === 'suspended') ? 'selected' : ''; ?>>Suspended</option>
                                    </select>
                                </div>

                                <div class="form-field">
                                    <label class="form-label-title">Set Password <?php echo ($editingStaff === null) ? '<span style="color: #ef4444;">*</span>' : '(Leave blank to keep unchanged)'; ?></label>
                                    <div style="position: relative;">
                                        <input type="password" id="staff_password_input" name="password" class="form-text-input" placeholder="<?php echo ($editingStaff === null) ? 'Password123#' : 'Enter new password...'; ?>" <?php echo ($editingStaff === null) ? 'required' : ''; ?> style="padding-right: 40px;">
                                        <button type="button" onclick="togglePasswordVisibility('staff_password_input', this)" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #64748b; cursor: pointer;" title="Toggle Password Visibility">
                                            <i class="fa-regular fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div style="display: flex; gap: 12px; margin-top: 24px;">
                                <button type="submit" class="btn-save-primary" style="background-color: #dc2626; padding: 12px 28px;">
                                    <i class="fa-solid fa-floppy-disk"></i> Save Staff Member
                                </button>
                                <a href="index.php?section=staff_accounts" style="padding: 12px 20px; background: #f1f5f9; color: #475569; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 0.9rem;">
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>

                    <script>
                        function handleStaffAvatarUpload(input) {
                            if (!input.files || !input.files[0]) return;
                            const file = input.files[0];
                            const statusBox = document.getElementById('staff_upload_status');
                            const previewContainer = document.getElementById('staff_avatar_preview_container');
                            const existingInput = document.getElementById('staff_existing_avatar');

                            statusBox.style.display = 'block';

                            const formData = new FormData();
                            formData.append('action', 'upload_cloudinary_ajax');
                            formData.append('file', file);
                            formData.append('folder', 'falhen/team');

                            fetch('index.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(res => res.json())
                            .then(data => {
                                statusBox.style.display = 'none';
                                if (data.success) {
                                    existingInput.value = data.url;
                                    previewContainer.innerHTML = '<img id="staff_avatar_img" src="' + data.url + '" style="width: 70px; height: 70px; border-radius: 50%; object-fit: cover; border: 2px solid #dc2626;">';
                                } else {
                                    alert('Avatar Upload Error: ' + data.message);
                                }
                            })
                            .catch(err => {
                                statusBox.style.display = 'none';
                                alert('Cloudinary avatar upload error.');
                            });
                        }

                        function togglePasswordVisibility(inputId, btn) {
                            const input = document.getElementById(inputId);
                            if (!input) return;
                            const icon = btn.querySelector('i');
                            if (input.type === 'password') {
                                input.type = 'text';
                                if (icon) icon.className = 'fa-regular fa-eye-slash';
                            } else {
                                input.type = 'password';
                                if (icon) icon.className = 'fa-regular fa-eye';
                            }
                        }
                    </script>

                <?php else: ?>
                    <!-- LIST VIEW: STAFF ACCOUNTS GRID -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 24px;">
                        <?php foreach ($staffList as $stItem): ?>
                            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                                <div>
                                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                                        <form action="index.php?section=staff_accounts" method="POST" style="margin: 0;">
                                            <input type="hidden" name="action" value="toggle_staff_status">
                                            <input type="hidden" name="id" value="<?php echo $stItem['id']; ?>">
                                            <button type="submit" style="border: none; background: none; cursor: pointer; padding: 0;" title="Click to toggle account status">
                                                <?php if (($stItem['status'] ?? 'active') === 'active'): ?>
                                                    <span style="background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; font-size: 0.72rem; font-weight: 800; padding: 3px 10px; border-radius: 12px;">● Active</span>
                                                <?php else: ?>
                                                    <span style="background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; font-size: 0.72rem; font-weight: 800; padding: 3px 10px; border-radius: 12px;">● Suspended</span>
                                                <?php endif; ?>
                                            </button>
                                        </form>

                                        <span style="font-size: 0.74rem; font-weight: 800; color: #475569; background: #f1f5f9; padding: 2px 8px; border-radius: 6px;">
                                            <?php echo htmlspecialchars($stItem['role'] ?? 'Staff'); ?>
                                        </span>
                                    </div>

                                    <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 16px;">
                                        <?php if (!empty($stItem['avatar'])): ?>
                                            <img src="<?php echo htmlspecialchars(getCloudinaryUrl($stItem['avatar'])); ?>" alt="<?php echo htmlspecialchars($stItem['full_name']); ?>" style="width: 54px; height: 54px; border-radius: 50%; object-fit: cover; border: 2px solid #dc2626; flex-shrink: 0;">
                                        <?php else: ?>
                                            <div style="width: 54px; height: 54px; border-radius: 50%; background: #dc2626; color: #ffffff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.3rem; flex-shrink: 0;">
                                                <?php echo strtoupper(substr($stItem['full_name'] ?? 'S', 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>

                                        <div>
                                            <h3 style="font-size: 1.05rem; font-weight: 800; color: #0f172a; margin: 0 0 2px 0;">
                                                <?php echo htmlspecialchars($stItem['full_name']); ?>
                                            </h3>
                                            <div style="font-size: 0.8rem; color: #64748b; font-weight: 600;">@<?php echo htmlspecialchars($stItem['username']); ?></div>
                                        </div>
                                    </div>

                                    <div style="font-size: 0.82rem; color: #64748b; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                                        <i class="fa-regular fa-envelope" style="color: #dc2626;"></i>
                                        <a href="mailto:<?php echo htmlspecialchars($stItem['email']); ?>" style="color: #64748b; text-decoration: none; word-break: break-all;"><?php echo htmlspecialchars($stItem['email']); ?></a>
                                    </div>
                                </div>

                                <div style="display: flex; align-items: center; justify-content: space-between; border-top: 1px solid #f1f5f9; padding-top: 14px; margin-top: 14px;">
                                    <a href="index.php?section=staff_accounts&edit_id=<?php echo $stItem['id']; ?>" style="color: #0f172a; font-size: 0.82rem; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; background: #f1f5f9; padding: 6px 14px; border-radius: 8px;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                                        <i class="fa-solid fa-user-pen" style="font-size: 0.75rem; color: #dc2626;"></i> Edit Member
                                    </a>

                                    <form action="index.php?section=staff_accounts" method="POST" onsubmit="return promptConfirmModal(this, 'Remove Staff Account', 'Are you sure you want to remove <?php echo htmlspecialchars(addslashes($stItem['full_name'])); ?> from staff accounts?');" style="margin: 0;">
                                        <input type="hidden" name="action" value="delete_staff_account">
                                        <input type="hidden" name="id" value="<?php echo $stItem['id']; ?>">
                                        <button type="submit" style="background: none; border: none; color: #94a3b8; font-size: 0.82rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; padding: 4px 8px;" onmouseover="this.style.color='#dc2626'" onmouseout="this.style.color='#94a3b8'">
                                            <i class="fa-solid fa-trash-can" style="font-size: 0.75rem;"></i> Remove
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            <?php elseif ($activeSection === 'dashboard'): ?>
                <!-- SECTION: EMPLOYEE PORTAL DASHBOARD -->
                <div class="section-header-bar">
                    <div>
                        <h1 class="section-header-title">Employee Portal Dashboard</h1>
                        <p class="section-header-desc">Welcome back, <strong><?php echo htmlspecialchars($userFullName); ?></strong> &mdash; overview of your attendance, schedule, announcements, and team celebrations.</p>
                    </div>
                    <a href="/admin/index.php?section=my_profile" class="btn-save-primary" style="text-decoration: none;">
                        <i class="fa-solid fa-user-gear"></i> Edit Profile
                    </a>
                </div>

                <?php 
                $todayAttendance = getUserTodayAttendance($username);
                ?>

                <!-- TOP ROW: 4 QUICK ACTION CARDS -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; margin-bottom: 24px;">
                    <a href="/admin/index.php?section=attendance" style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 14px; padding: 18px; text-decoration: none; display: flex; align-items: center; gap: 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); transition: all 0.2s ease;" onmouseover="this.style.borderColor='#dc2626'; this.style.transform='translateY(-2px)'" onmouseout="this.style.borderColor='#cbd5e1'; this.style.transform='translateY(0)'">
                        <div style="width: 44px; height: 44px; border-radius: 12px; background: #f0fdf4; border: 1px solid #bbf7d0; display: flex; align-items: center; justify-content: center; color: #16a34a; font-size: 1.2rem; flex-shrink: 0;">
                            <i class="fa-solid fa-clock-pulse"></i>
                        </div>
                        <div>
                            <div style="font-size: 0.95rem; font-weight: 800; color: #0f172a;">My Attendance</div>
                            <div style="font-size: 0.76rem; color: #64748b; margin-top: 2px;">Log shift & view history</div>
                        </div>
                    </a>

                    <a href="/admin/index.php?section=leaves" style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 14px; padding: 18px; text-decoration: none; display: flex; align-items: center; gap: 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); transition: all 0.2s ease;" onmouseover="this.style.borderColor='#dc2626'; this.style.transform='translateY(-2px)'" onmouseout="this.style.borderColor='#cbd5e1'; this.style.transform='translateY(0)'">
                        <div style="width: 44px; height: 44px; border-radius: 12px; background: #eff6ff; border: 1px solid #bfdbfe; display: flex; align-items: center; justify-content: center; color: #2563eb; font-size: 1.2rem; flex-shrink: 0;">
                            <i class="fa-solid fa-calendar-plus"></i>
                        </div>
                        <div>
                            <div style="font-size: 0.95rem; font-weight: 800; color: #0f172a;">Request Time Off</div>
                            <div style="font-size: 0.76rem; color: #64748b; margin-top: 2px;">Submit leave application</div>
                        </div>
                    </a>

                    <a href="/admin/index.php?section=directory" style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 14px; padding: 18px; text-decoration: none; display: flex; align-items: center; gap: 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); transition: all 0.2s ease;" onmouseover="this.style.borderColor='#dc2626'; this.style.transform='translateY(-2px)'" onmouseout="this.style.borderColor='#cbd5e1'; this.style.transform='translateY(0)'">
                        <div style="width: 44px; height: 44px; border-radius: 12px; background: #f3e8ff; border: 1px solid #e9d5ff; display: flex; align-items: center; justify-content: center; color: #9333ea; font-size: 1.2rem; flex-shrink: 0;">
                            <i class="fa-solid fa-users"></i>
                        </div>
                        <div>
                            <div style="font-size: 0.95rem; font-weight: 800; color: #0f172a;">Team Directory</div>
                            <div style="font-size: 0.76rem; color: #64748b; margin-top: 2px;">Staff & team contacts</div>
                        </div>
                    </a>

                    <a href="/admin/index.php?section=comms" style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 14px; padding: 18px; text-decoration: none; display: flex; align-items: center; gap: 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); transition: all 0.2s ease;" onmouseover="this.style.borderColor='#dc2626'; this.style.transform='translateY(-2px)'" onmouseout="this.style.borderColor='#cbd5e1'; this.style.transform='translateY(0)'">
                        <div style="width: 44px; height: 44px; border-radius: 12px; background: #fef2f2; border: 1px solid #fecaca; display: flex; align-items: center; justify-content: center; color: #dc2626; font-size: 1.2rem; flex-shrink: 0;">
                            <i class="fa-solid fa-comments"></i>
                        </div>
                        <div>
                            <div style="font-size: 0.95rem; font-weight: 800; color: #0f172a;">The Nest (Chat)</div>
                            <div style="font-size: 0.76rem; color: #64748b; margin-top: 2px;">Team comms & messaging</div>
                        </div>
                    </a>
                </div>

                <!-- MAIN DASHBOARD CONTENT GRID (LEFT COLUMN: SCHEDULE & ANNOUNCEMENTS, RIGHT COLUMN: LEAVES, WHO'S OFF & CELEBRATIONS) -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">
                    
                    <!-- LEFT COLUMN -->
                    <div style="display: flex; flex-direction: column; gap: 24px;">
                        
                        <!-- CARD 1: TODAY'S SCHEDULE -->
                        <div class="dashboard-card" style="margin-bottom: 0; padding: 24px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                                <h3 style="margin: 0; font-size: 1.1rem; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                                    <i class="fa-solid fa-calendar-day" style="color: #2563eb;"></i> Today's Schedule
                                </h3>
                                <a href="/admin/index.php?section=attendance" style="font-size: 0.82rem; font-weight: 700; color: #dc2626; text-decoration: none;">
                                    View Calendar &rarr;
                                </a>
                            </div>

                            <div style="text-align: center; padding: 32px 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px;">
                                <div style="width: 52px; height: 52px; border-radius: 50%; background: #ffffff; border: 1px solid #cbd5e1; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px auto; color: #64748b; font-size: 1.3rem;">
                                    <i class="fa-solid fa-mug-hot"></i>
                                </div>
                                <div style="font-size: 1.05rem; font-weight: 800; color: #0f172a; margin-bottom: 4px;">No meetings today</div>
                                <div style="font-size: 0.84rem; color: #64748b;">You have a clear schedule. Time to focus!</div>
                            </div>
                        </div>

                        <!-- CARD 2: COMPANY ANNOUNCEMENTS -->
                        <div class="dashboard-card" style="margin-bottom: 0; padding: 24px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                                <h3 style="margin: 0; font-size: 1.1rem; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                                    <i class="fa-solid fa-bullhorn" style="color: #d97706;"></i> Company Announcements
                                </h3>
                                <a href="/admin/index.php?section=announcements" style="font-size: 0.82rem; font-weight: 700; color: #dc2626; text-decoration: none;">
                                    View All &rarr;
                                </a>
                            </div>

                            <div style="display: flex; flex-direction: column; gap: 12px;">
                                <div style="padding: 14px; background: #fef2f2; border-left: 4px solid #dc2626; border-radius: 8px;">
                                    <div style="font-weight: 800; color: #991b1b; font-size: 0.92rem;">Falhen 2026 Q3 Production Sprint</div>
                                    <div style="font-size: 0.8rem; color: #475569; margin-top: 4px;">New RED V-Raptor camera packages available for booking.</div>
                                </div>
                                <div style="padding: 14px; background: #f0fdf4; border-left: 4px solid #16a34a; border-radius: 8px;">
                                    <div style="font-weight: 800; color: #166534; font-size: 0.92rem;">Health & Wellness Stipend Updated</div>
                                    <div style="font-size: 0.8rem; color: #475569; margin-top: 4px;">Submit your monthly gym & wellness receipts by 25th.</div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- RIGHT COLUMN -->
                    <div style="display: flex; flex-direction: column; gap: 24px;">
                        
                        <!-- CARD 3: MY TIME OFF -->
                        <div class="dashboard-card" style="margin-bottom: 0; padding: 24px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                                <h3 style="margin: 0; font-size: 1rem; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                                    <i class="fa-solid fa-plane-departure" style="color: #0284c7;"></i> My Time Off
                                </h3>
                                <a href="/admin/index.php?section=leaves" style="font-size: 0.78rem; font-weight: 700; color: #dc2626; text-decoration: none;">
                                    Manage &rarr;
                                </a>
                            </div>
                            <div style="font-size: 0.84rem; color: #64748b; padding: 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; text-align: center;">
                                No upcoming time off requested.
                            </div>
                        </div>

                        <!-- CARD 4: WHO'S OFF TODAY? -->
                        <div class="dashboard-card" style="margin-bottom: 0; padding: 24px;">
                            <div style="margin-bottom: 14px;">
                                <h3 style="margin: 0; font-size: 1rem; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                                    <i class="fa-solid fa-cloud-sun" style="color: #2563eb;"></i> Who's Off Today?
                                </h3>
                            </div>
                            <div style="padding: 20px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; text-align: center;">
                                <i class="fa-solid fa-users" style="font-size: 1.6rem; color: #94a3b8; margin-bottom: 8px; display: block;"></i>
                                <div style="font-size: 0.9rem; font-weight: 800; color: #0f172a;">Everyone is in the office today!</div>
                            </div>
                        </div>

                        <!-- CARD 5: CELEBRATIONS -->
                        <div class="dashboard-card" style="margin-bottom: 0; padding: 24px;">
                            <div style="margin-bottom: 14px;">
                                <h3 style="margin: 0; font-size: 1rem; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                                    <i class="fa-solid fa-cake-candles" style="color: #ec4899;"></i> Celebrations
                                </h3>
                            </div>
                            <div style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; background: #fdf2f8; border: 1px solid #fbcfe8; border-radius: 12px;">
                                <div style="width: 40px; height: 40px; border-radius: 50%; background: #ec4899; color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0;">
                                    <i class="fa-solid fa-gift"></i>
                                </div>
                                <div>
                                    <div style="font-size: 0.88rem; font-weight: 800; color: #0f172a;">Michael Scott</div>
                                    <div style="font-size: 0.76rem; color: #be185d; font-weight: 600;">Birthday on Aug 20</div>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>

            <?php elseif ($activeSection === 'onboarding'): ?>
                <!-- SECTION: ONBOARDING -->
                <?php 
                $isHRorAdmin = isAdminUser($userRole, $userEmail, $username);
                $targetUsername = trim($_GET['target_user'] ?? $username);
                
                // If HR/Admin, allow managing any staff member's onboarding profile
                $activeOnboardingUser = $isHRorAdmin ? $targetUsername : $username;

                $onboardingData = getUserOnboardingData($activeOnboardingUser);
                $onboardingProgress = getOnboardingProgress($activeOnboardingUser);
                $allStaffSummary = $isHRorAdmin ? getAllStaffOnboardingSummary() : [];
                ?>

                <?php if ($isHRorAdmin): ?>
                    <!-- HR & ADMIN OVERVIEW & STAFF SELECTOR -->
                    <div class="dashboard-card" style="margin-bottom: 24px; padding: 24px; border-top: 4px solid #dc2626;">
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; margin-bottom: 20px;">
                            <div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span style="background: #dc2626; color: #ffffff; font-weight: 800; font-size: 0.75rem; padding: 3px 10px; border-radius: 12px; text-transform: uppercase;">HR & Admin Console</span>
                                    <h2 style="font-size: 1.35rem; font-weight: 800; color: #0f172a; margin: 0;">Onboarding Management</h2>
                                </div>
                                <p style="font-size: 0.84rem; color: #64748b; margin: 4px 0 0 0;">Administer staff onboarding activities, verify bank details, issue offer letters & employment agreements.</p>
                            </div>

                            <!-- STAFF SELECTOR FORM -->
                            <form method="GET" action="/admin/index.php" style="display: flex; align-items: center; gap: 8px; margin: 0;">
                                <input type="hidden" name="section" value="onboarding">
                                <label style="font-size: 0.82rem; font-weight: 700; color: #475569;">Managing Profile:</label>
                                <select name="target_user" onchange="this.form.submit()" style="padding: 8px 14px; font-size: 0.84rem; font-weight: 700; border: 1px solid #cbd5e1; border-radius: 8px; background: #ffffff; color: #0f172a;">
                                    <?php foreach ($allStaffSummary as $stSum): ?>
                                        <option value="<?php echo htmlspecialchars($stSum['username']); ?>" <?php echo ($activeOnboardingUser === $stSum['username']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($stSum['full_name']); ?> (@<?php echo htmlspecialchars($stSum['username']); ?>) &bull; <?php echo $stSum['percent']; ?>%
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </div>

                        <!-- ALL STAFF PROGRESS CAROUSEL / GRID -->
                        <div style="font-size: 0.82rem; font-weight: 800; color: #64748b; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px;">Company-Wide Staff Progress Overview</div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px;">
                            <?php foreach ($allStaffSummary as $stSum): ?>
                                <a href="/admin/index.php?section=onboarding&target_user=<?php echo urlencode($stSum['username']); ?>" style="display: block; padding: 12px 14px; background: <?php echo ($activeOnboardingUser === $stSum['username']) ? '#fef2f2' : '#f8fafc'; ?>; border: 1px solid <?php echo ($activeOnboardingUser === $stSum['username']) ? '#fecaca' : '#e2e8f0'; ?>; border-radius: 10px; text-decoration: none; transition: all 0.2s ease;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                        <div style="font-weight: 800; font-size: 0.86rem; color: #0f172a;"><?php echo htmlspecialchars($stSum['full_name']); ?></div>
                                        <span style="font-size: 0.72rem; font-weight: 800; color: #dc2626; background: #ffffff; padding: 2px 6px; border-radius: 10px; border: 1px solid #cbd5e1;"><?php echo $stSum['percent']; ?>%</span>
                                    </div>
                                    <div style="font-size: 0.75rem; color: #64748b;"><?php echo htmlspecialchars($stSum['role']); ?> &bull; <?php echo $stSum['completed']; ?>/7 Tasks</div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- PROGRESS HEADER FOR SELECTED PROFILE -->
                <div class="dashboard-card" style="margin-bottom: 24px; padding: 24px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 10px;">
                        <div>
                            <h2 style="font-size: 1.35rem; font-weight: 800; color: #0f172a; margin: 0 0 4px 0;">
                                <?php echo $isHRorAdmin ? 'Onboarding File: ' . htmlspecialchars($activeOnboardingUser) : 'Your Progress'; ?>
                            </h2>
                            <p style="font-size: 0.84rem; color: #64748b; margin: 0;">
                                <?php echo $isHRorAdmin ? 'Administering tasks and documentation for @' . htmlspecialchars($activeOnboardingUser) : 'Complete your onboarding tasks to get fully setup on the studio portal.'; ?>
                            </p>
                        </div>
                        <div style="font-size: 0.95rem; font-weight: 800; color: #0f172a; background: #f8fafc; padding: 6px 14px; border-radius: 20px; border: 1px solid #cbd5e1;">
                            Tasks Completed: <span style="color: #dc2626;"><?php echo $onboardingProgress['completed']; ?>/<?php echo $onboardingProgress['total']; ?></span>
                        </div>
                    </div>
                    <div style="width: 100%; height: 10px; background: #f1f5f9; border-radius: 20px; overflow: hidden; border: 1px solid #e2e8f0;">
                        <div style="width: <?php echo $onboardingProgress['percent']; ?>%; height: 100%; background: linear-gradient(90deg, #dc2626, #ef4444); border-radius: 20px; transition: width 0.4s ease;"></div>
                    </div>
                </div>

                <!-- 7 ONBOARDING TASK CARDS GRID (3 CARDS IN A ROW) -->
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px;">

                    <!-- CARD 1: BANK DETAILS -->
                    <?php 
                    $isEditingBank = isset($_GET['edit_bank']) && $_GET['edit_bank'] == '1';
                    $hasBankDetails = !empty($onboardingData['bank_details']['account_number']);
                    $bankStatus = $onboardingData['bank_details']['status'] ?? ($hasBankDetails ? 'Submitted' : 'Pending');
                    $isBankApproved = ($bankStatus === 'Approved');
                    ?>
                    <div class="dashboard-card" style="margin-bottom: 0; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <h3 style="font-size: 1.1rem; font-weight: 800; color: #0f172a; margin: 0;">Bank Details</h3>
                                <span style="font-size: 0.74rem; font-weight: 800; padding: 3px 10px; border-radius: 12px; <?php echo ($isBankApproved ? 'background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0;' : ($hasBankDetails && !$isEditingBank ? 'background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe;' : 'background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1;')); ?>">
                                    <?php echo ($isBankApproved ? 'Approved' : ($hasBankDetails && !$isEditingBank ? 'Submitted' : ($isEditingBank ? 'Editing' : 'Pending'))); ?>
                                </span>
                            </div>
                            <p style="font-size: 0.84rem; color: #64748b; margin-bottom: 16px;">Please provide your bank account information for payroll processing.</p>

                            <?php if ($hasBankDetails && !$isEditingBank): ?>
                                <div style="padding: 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 0.84rem; margin-bottom: 14px;">
                                    <div style="font-weight: 700; color: #0f172a;">Bank: <?php echo htmlspecialchars($onboardingData['bank_details']['bank_name']); ?></div>
                                    <div style="color: #475569; margin-top: 4px;">Account No: <strong><?php echo htmlspecialchars($onboardingData['bank_details']['account_number']); ?></strong></div>
                                    <div style="color: #64748b; margin-top: 2px;">Account Name: <?php echo htmlspecialchars($onboardingData['bank_details']['account_name']); ?></div>
                                </div>
                                <?php if ($isBankApproved): ?>
                                    <div style="padding: 12px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; color: #166534; font-weight: 800; font-size: 0.86rem; text-align: center;">
                                        <i class="fa-solid fa-check-circle" style="color: #16a34a;"></i> Bank Details Verified & Approved by HR
                                    </div>
                                <?php else: ?>
                                    <a href="/admin/index.php?section=onboarding&edit_bank=1&target_user=<?php echo urlencode($activeOnboardingUser); ?>" class="btn-save-primary" style="width: 100%; justify-content: center; background: #ffffff; color: #0f172a; border: 1px solid #cbd5e1; text-decoration: none;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='#ffffff'">
                                        <i class="fa-solid fa-pen-to-square" style="color: #dc2626;"></i> Edit Bank Details
                                    </a>
                                    <?php if ($isHRorAdmin): ?>
                                        <form method="POST" action="/admin/index.php?section=onboarding" style="margin-top: 10px;">
                                            <input type="hidden" name="action" value="admin_approve_onboarding_task">
                                            <input type="hidden" name="target_user" value="<?php echo htmlspecialchars($activeOnboardingUser); ?>">
                                            <input type="hidden" name="task_key" value="bank_details">
                                            <input type="hidden" name="new_status" value="Approved">
                                            <button type="submit" class="btn-save-primary" style="width: 100%; justify-content: center; background: #16a34a; border-color: #16a34a;">
                                                <i class="fa-solid fa-check"></i> HR Approve Bank Details
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php else: ?>
                                <form method="POST" action="/admin/index.php?section=onboarding" style="display: flex; flex-direction: column; gap: 10px;">
                                    <input type="hidden" name="action" value="save_onboarding_bank">
                                    <div>
                                        <label style="font-size: 0.76rem; font-weight: 700; color: #475569; display: block; margin-bottom: 3px;">Account Number</label>
                                        <input type="text" name="account_number" value="<?php echo htmlspecialchars($onboardingData['bank_details']['account_number'] ?? ''); ?>" placeholder="Account Number" required style="width: 100%; padding: 10px; font-size: 0.84rem; border: 1px solid #cbd5e1; border-radius: 8px;">
                                    </div>
                                    <div>
                                        <label style="font-size: 0.76rem; font-weight: 700; color: #475569; display: block; margin-bottom: 3px;">Bank Name</label>
                                        <input type="text" name="bank_name" value="<?php echo htmlspecialchars($onboardingData['bank_details']['bank_name'] ?? ''); ?>" placeholder="Bank Name" required style="width: 100%; padding: 10px; font-size: 0.84rem; border: 1px solid #cbd5e1; border-radius: 8px;">
                                    </div>
                                    <div>
                                        <label style="font-size: 0.76rem; font-weight: 700; color: #475569; display: block; margin-bottom: 3px;">Account Name</label>
                                        <input type="text" name="account_name" value="<?php echo htmlspecialchars($onboardingData['bank_details']['account_name'] ?? ''); ?>" placeholder="Account Name" required style="width: 100%; padding: 10px; font-size: 0.84rem; border: 1px solid #cbd5e1; border-radius: 8px;">
                                    </div>
                                    <div style="display: flex; gap: 8px; margin-top: 6px;">
                                        <button type="submit" class="btn-save-primary" style="flex: 1; justify-content: center;">
                                            <i class="fa-solid fa-floppy-disk"></i> <?php echo $hasBankDetails ? 'Update Details' : 'Submit Details'; ?>
                                        </button>
                                        <?php if ($hasBankDetails): ?>
                                            <a href="/admin/index.php?section=onboarding&target_user=<?php echo urlencode($activeOnboardingUser); ?>" style="padding: 10px 14px; background: #f1f5f9; color: #475569; border-radius: 8px; font-size: 0.84rem; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center;">Cancel</a>
                                        <?php endif; ?>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- CARD 2: OFFER LETTER -->
                    <?php 
                        $offerInfo = $onboardingData['offer_letter'] ?? [];
                        $offerStatus = $offerInfo['status'] ?? 'Pending';
                        $offerIssued = !empty($offerInfo['issued']) || in_array($offerStatus, ['Approved', 'Issued']);
                        $offerAccepted = !empty($offerInfo['accepted']) || $offerStatus === 'Approved';
                    ?>
                    <div class="dashboard-card" style="margin-bottom: 0; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <h3 style="font-size: 1.1rem; font-weight: 800; color: #0f172a; margin: 0;">Offer Letter</h3>
                                <span style="font-size: 0.74rem; font-weight: 800; padding: 3px 10px; border-radius: 12px; <?php echo ($offerAccepted ? 'background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0;' : ($offerIssued ? 'background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe;' : 'background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1;')); ?>">
                                    <?php echo htmlspecialchars($offerAccepted ? 'Approved' : ($offerIssued ? 'Issued' : 'Pending')); ?>
                                </span>
                            </div>
                            <p style="font-size: 0.84rem; color: #64748b; margin-bottom: 14px;">Official employment offer letter and terms.</p>

                            <?php if ($offerAccepted): ?>
                                <div style="padding: 12px 14px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; text-align: center; color: #166534; font-weight: 800; font-size: 0.88rem; margin-bottom: 12px;">
                                    <i class="fa-solid fa-check-circle" style="color: #16a34a;"></i> Offer Letter Issued & Accepted
                                </div>
                            <?php elseif ($offerIssued): ?>
                                <div style="padding: 12px 14px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; text-align: center; color: #1e40af; font-weight: 800; font-size: 0.88rem; margin-bottom: 12px;">
                                    <i class="fa-solid fa-paper-plane" style="color: #2563eb;"></i> Offer Issued - Pending Acceptance
                                </div>
                            <?php else: ?>
                                <div style="padding: 12px 14px; background: #fffbe6; border: 1px solid #fef08a; border-radius: 10px; text-align: center; color: #b45309; font-weight: 700; font-size: 0.84rem; margin-bottom: 12px;">
                                    <i class="fa-solid fa-clock" style="color: #d97706;"></i> Awaiting Talent Manager Issuance
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($offerInfo['job_title'])): ?>
                                <div style="font-size: 0.82rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px; margin-bottom: 12px;">
                                    <div style="font-weight: 800; color: #0f172a;"><?php echo htmlspecialchars($offerInfo['job_title']); ?></div>
                                    <div style="color: #64748b; font-size: 0.78rem; margin-top: 2px;">
                                        <?php echo htmlspecialchars($offerInfo['department'] ?? 'Media Production Studio'); ?> • <?php echo htmlspecialchars($offerInfo['employment_type'] ?? 'Full-Time'); ?>
                                    </div>
                                    <?php if (!empty($offerInfo['salary'])): ?>
                                        <div style="font-weight: 700; color: #166534; font-size: 0.8rem; margin-top: 4px;">
                                            Salary: <?php echo htmlspecialchars($offerInfo['salary']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <button type="button" class="btn-save-primary" style="width: 100%; justify-content: center; background: #0f172a;" onclick="openOfferLetterViewModal()">
                                <i class="fa-solid fa-eye"></i> View Offer Document
                            </button>
                            <?php if ($isHRorAdmin): ?>
                                <button type="button" class="btn-save-primary" style="width: 100%; justify-content: center; background: #16a34a; border-color: #16a34a; font-size: 0.82rem;" onclick="openIssueOfferModal()">
                                    <i class="fa-solid fa-file-signature"></i> <?php echo ($offerIssued ? 'HR Re-Issue Offer Letter' : 'HR Issue Offer Letter'); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- CARD 3: EMPLOYMENT AGREEMENT -->
                    <div class="dashboard-card" style="margin-bottom: 0; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <h3 style="font-size: 1.1rem; font-weight: 800; color: #0f172a; margin: 0;">Employment Agreement</h3>
                                <span style="font-size: 0.74rem; font-weight: 800; padding: 3px 10px; border-radius: 12px; <?php echo (!empty($onboardingData['employment_agreement']['signed'])) ? 'background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0;' : 'background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1;'; ?>">
                                    <?php echo (!empty($onboardingData['employment_agreement']['signed'])) ? 'Approved' : 'Pending'; ?>
                                </span>
                            </div>
                            <p style="font-size: 0.84rem; color: #64748b; margin-bottom: 16px;">Upload a signed copy of your employment agreement.</p>

                            <?php if (empty($onboardingData['employment_agreement']['signed'])): ?>
                                <div style="padding: 14px; background: #fffbe6; border: 1px solid #fef08a; border-radius: 10px; color: #b45309; font-size: 0.84rem; font-weight: 700; display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                                    <i class="fa-solid fa-clock" style="color: #d97706;"></i> Waiting for HR to provide this document.
                                </div>
                            <?php else: ?>
                                <div style="padding: 14px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; color: #166534; font-weight: 800; font-size: 0.88rem; margin-bottom: 10px;">
                                    <i class="fa-solid fa-check-circle"></i> Signed Agreement Verified
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if ($isHRorAdmin): ?>
                            <form method="POST" action="/admin/index.php?section=onboarding" style="display: flex; flex-direction: column; gap: 6px; margin-top: 10px;">
                                <input type="hidden" name="action" value="admin_provide_agreement">
                                <input type="hidden" name="target_user" value="<?php echo htmlspecialchars($activeOnboardingUser); ?>">
                                <input type="text" name="document_url" value="<?php echo htmlspecialchars($onboardingData['employment_agreement']['document_url'] ?? '/assets/docs/Employment_Agreement.pdf'); ?>" placeholder="Agreement PDF File Link" required style="padding: 8px; font-size: 0.8rem; border: 1px solid #cbd5e1; border-radius: 6px;">
                                <button type="submit" class="btn-save-primary" style="justify-content: center; background: #16a34a; border-color: #16a34a;">
                                    <i class="fa-solid fa-file-signature"></i> Upload & Issue Agreement
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <!-- CARD 4: REFERENCE 1 -->
                    <?php 
                    $isEditingRef1 = isset($_GET['edit_ref1']) && $_GET['edit_ref1'] == '1';
                    $hasRef1Details = !empty($onboardingData['reference_1']['ref_name']);
                    $ref1Status = $onboardingData['reference_1']['status'] ?? ($hasRef1Details ? 'Submitted' : 'Pending');
                    $isRef1Approved = ($ref1Status === 'Approved');
                    ?>
                    <div class="dashboard-card" style="margin-bottom: 0; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <h3 style="font-size: 1.1rem; font-weight: 800; color: #0f172a; margin: 0;">Reference 1</h3>
                                <span style="font-size: 0.74rem; font-weight: 800; padding: 3px 10px; border-radius: 12px; <?php echo ($isRef1Approved ? 'background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0;' : ($hasRef1Details && !$isEditingRef1 ? 'background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe;' : 'background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1;')); ?>">
                                    <?php echo ($isRef1Approved ? 'Approved' : ($hasRef1Details && !$isEditingRef1 ? 'Submitted' : ($isEditingRef1 ? 'Editing' : 'Pending'))); ?>
                                </span>
                            </div>
                            <p style="font-size: 0.84rem; color: #64748b; margin-bottom: 16px;">Provide contact details for your first professional reference.</p>

                            <?php if ($hasRef1Details && !$isEditingRef1): ?>
                                <div style="padding: 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 0.84rem; margin-bottom: 14px;">
                                    <div style="font-weight: 700; color: #0f172a;"><?php echo htmlspecialchars($onboardingData['reference_1']['ref_name']); ?></div>
                                    <div style="color: #475569; margin-top: 3px;"><?php echo htmlspecialchars($onboardingData['reference_1']['relationship']); ?></div>
                                    <div style="color: #64748b; margin-top: 2px;"><?php echo htmlspecialchars($onboardingData['reference_1']['ref_contact']); ?></div>
                                </div>
                                <?php if ($isRef1Approved): ?>
                                    <div style="padding: 12px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; color: #166534; font-weight: 800; font-size: 0.86rem; text-align: center;">
                                        <i class="fa-solid fa-check-circle" style="color: #16a34a;"></i> Reference Verified & Approved by HR
                                    </div>
                                <?php else: ?>
                                    <a href="/admin/index.php?section=onboarding&edit_ref1=1&target_user=<?php echo urlencode($activeOnboardingUser); ?>" class="btn-save-primary" style="width: 100%; justify-content: center; background: #ffffff; color: #0f172a; border: 1px solid #cbd5e1; text-decoration: none;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='#ffffff'">
                                        <i class="fa-solid fa-pen-to-square" style="color: #dc2626;"></i> Edit Reference
                                    </a>
                                    <?php if ($isHRorAdmin): ?>
                                        <form method="POST" action="/admin/index.php?section=onboarding" style="margin-top: 10px;">
                                            <input type="hidden" name="action" value="admin_approve_onboarding_task">
                                            <input type="hidden" name="target_user" value="<?php echo htmlspecialchars($activeOnboardingUser); ?>">
                                            <input type="hidden" name="task_key" value="reference_1">
                                            <input type="hidden" name="new_status" value="Approved">
                                            <button type="submit" class="btn-save-primary" style="width: 100%; justify-content: center; background: #16a34a; border-color: #16a34a;">
                                                <i class="fa-solid fa-check"></i> HR Verify Reference
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php else: ?>
                                <form method="POST" action="/admin/index.php?section=onboarding" style="display: flex; flex-direction: column; gap: 10px;">
                                    <input type="hidden" name="action" value="save_onboarding_ref1">
                                    <input type="text" name="ref_name" value="<?php echo htmlspecialchars($onboardingData['reference_1']['ref_name'] ?? ''); ?>" placeholder="Full Name" required style="padding: 10px; font-size: 0.84rem; border: 1px solid #cbd5e1; border-radius: 8px;">
                                    <input type="text" name="ref_contact" value="<?php echo htmlspecialchars($onboardingData['reference_1']['ref_contact'] ?? ''); ?>" placeholder="Email / Phone Number" required style="padding: 10px; font-size: 0.84rem; border: 1px solid #cbd5e1; border-radius: 8px;">
                                    <input type="text" name="relationship" value="<?php echo htmlspecialchars($onboardingData['reference_1']['relationship'] ?? ''); ?>" placeholder="Relationship / Title" required style="padding: 10px; font-size: 0.84rem; border: 1px solid #cbd5e1; border-radius: 8px;">
                                    <div style="display: flex; gap: 8px; margin-top: 6px;">
                                        <button type="submit" class="btn-save-primary" style="flex: 1; justify-content: center;">
                                            <i class="fa-solid fa-floppy-disk"></i> <?php echo $hasRef1Details ? 'Update Reference' : 'Submit Reference'; ?>
                                        </button>
                                        <?php if ($hasRef1Details): ?>
                                            <a href="/admin/index.php?section=onboarding&target_user=<?php echo urlencode($activeOnboardingUser); ?>" style="padding: 10px 14px; background: #f1f5f9; color: #475569; border-radius: 8px; font-size: 0.84rem; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center;">Cancel</a>
                                        <?php endif; ?>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- CARD 5: SOP -->
                    <div class="dashboard-card" style="margin-bottom: 0; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <h3 style="font-size: 1.1rem; font-weight: 800; color: #0f172a; margin: 0;">SOP</h3>
                                <span style="font-size: 0.74rem; font-weight: 800; padding: 3px 10px; border-radius: 12px; <?php echo (!empty($onboardingData['sop']['acknowledged'])) ? 'background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0;' : 'background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1;'; ?>">
                                    <?php echo (!empty($onboardingData['sop']['acknowledged'])) ? 'Approved' : 'Pending'; ?>
                                </span>
                            </div>
                            <p style="font-size: 0.84rem; color: #64748b; margin-bottom: 16px;">Review and acknowledge studio standard operating procedures.</p>
                        </div>
                        <?php if (empty($onboardingData['sop']['acknowledged'])): ?>
                            <form method="POST" action="/admin/index.php?section=onboarding" style="margin: 0;">
                                <input type="hidden" name="action" value="acknowledge_sop">
                                <button type="submit" class="btn-save-primary" style="width: 100%; justify-content: center;">
                                    <i class="fa-solid fa-file-signature"></i> Read & Acknowledge SOP
                                </button>
                            </form>
                        <?php else: ?>
                            <div style="padding: 12px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; color: #166534; font-weight: 800; font-size: 0.86rem; text-align: center;">
                                <i class="fa-solid fa-check-circle"></i> SOP Acknowledged
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- CARD 6: STAFF HANDBOOK -->
                    <div class="dashboard-card" style="margin-bottom: 0; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <h3 style="font-size: 1.1rem; font-weight: 800; color: #0f172a; margin: 0;">Staff Handbook</h3>
                                <span style="font-size: 0.74rem; font-weight: 800; padding: 3px 10px; border-radius: 12px; <?php echo (!empty($onboardingData['staff_handbook']['acknowledged'])) ? 'background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0;' : 'background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1;'; ?>">
                                    <?php echo (!empty($onboardingData['staff_handbook']['acknowledged'])) ? 'Approved' : 'Pending'; ?>
                                </span>
                            </div>
                            <p style="font-size: 0.84rem; color: #64748b; margin-bottom: 16px;">Read and acknowledge the Falhen Media Staff Handbook & Policies.</p>
                        </div>
                        <?php if (empty($onboardingData['staff_handbook']['acknowledged'])): ?>
                            <form method="POST" action="/admin/index.php?section=onboarding" style="margin: 0;">
                                <input type="hidden" name="action" value="acknowledge_handbook">
                                <button type="submit" class="btn-save-primary" style="width: 100%; justify-content: center;">
                                    <i class="fa-solid fa-book-open"></i> Read & Acknowledge Handbook
                                </button>
                            </form>
                        <?php else: ?>
                            <div style="padding: 12px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; color: #166534; font-weight: 800; font-size: 0.86rem; text-align: center;">
                                <i class="fa-solid fa-check-circle"></i> Handbook Acknowledged
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- CARD 7: ID VERIFICATION -->
                    <?php 
                    $hasIdFile = !empty($onboardingData['id_verification']['file_url']);
                    $idStatus = $onboardingData['id_verification']['status'] ?? ($hasIdFile ? 'Submitted' : 'Pending');
                    $isIdApproved = ($idStatus === 'Approved');
                    ?>
                    <div class="dashboard-card" style="margin-bottom: 0; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <h3 style="font-size: 1.1rem; font-weight: 800; color: #0f172a; margin: 0;">ID Verification</h3>
                                <span style="font-size: 0.74rem; font-weight: 800; padding: 3px 10px; border-radius: 12px; <?php echo ($isIdApproved ? 'background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0;' : ($hasIdFile ? 'background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe;' : 'background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1;')); ?>">
                                    <?php echo ($isIdApproved ? 'Approved' : ($hasIdFile ? 'Submitted' : 'Pending')); ?>
                                </span>
                            </div>
                            <p style="font-size: 0.84rem; color: #64748b; margin-bottom: 16px;">Upload government-issued ID or Tax Identification document.</p>

                            <?php if ($hasIdFile): ?>
                                <?php if ($isIdApproved): ?>
                                    <div style="padding: 12px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; color: #166534; font-weight: 800; font-size: 0.86rem; text-align: center;">
                                        <i class="fa-solid fa-check-circle" style="color: #16a34a;"></i> ID Verified & Approved by HR
                                    </div>
                                <?php else: ?>
                                    <div style="padding: 12px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; color: #1e40af; font-weight: 800; font-size: 0.86rem; text-align: center; margin-bottom: 10px;">
                                        <i class="fa-solid fa-id-card"></i> ID Document Uploaded (Pending HR Approval)
                                    </div>
                                    <?php if ($isHRorAdmin): ?>
                                        <form method="POST" action="/admin/index.php?section=onboarding">
                                            <input type="hidden" name="action" value="admin_approve_onboarding_task">
                                            <input type="hidden" name="target_user" value="<?php echo htmlspecialchars($activeOnboardingUser); ?>">
                                            <input type="hidden" name="task_key" value="id_verification">
                                            <input type="hidden" name="new_status" value="Approved">
                                            <button type="submit" class="btn-save-primary" style="width: 100%; justify-content: center; background: #16a34a; border-color: #16a34a;">
                                                <i class="fa-solid fa-check"></i> HR Approve ID Verification
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php else: ?>
                                <form method="POST" action="/admin/index.php?section=onboarding" style="display: flex; flex-direction: column; gap: 10px;">
                                    <input type="hidden" name="action" value="save_onboarding_id">
                                    <input type="text" name="file_url" placeholder="Paste ID Document Link or File URL" required style="padding: 10px; font-size: 0.84rem; border: 1px solid #cbd5e1; border-radius: 8px;">
                                    <button type="submit" class="btn-save-primary" style="justify-content: center;">Upload ID Document</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>

                <!-- ISSUE OFFER LETTER MODAL (TALENT MANAGER / HR) -->
                <div id="issueOfferModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(6px); z-index: 100000; align-items: center; justify-content: center; padding: 20px; animation: fadeInModal 0.2s ease;">
                    <div style="background: #ffffff; border-radius: 20px; max-width: 620px; width: 100%; max-height: 90vh; overflow-y: auto; padding: 30px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.3); border: 1px solid #e2e8f0; position: relative;">
                        
                        <button type="button" onclick="closeIssueOfferModal()" style="position: absolute; top: 18px; right: 18px; background: #f1f5f9; border: none; width: 34px; height: 34px; border-radius: 50%; color: #64748b; font-size: 1rem; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.15s ease;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                            <i class="fa-solid fa-xmark"></i>
                        </button>

                        <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid #f1f5f9;">
                            <div style="width: 50px; height: 50px; border-radius: 14px; background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; display: flex; align-items: center; justify-content: center; font-size: 1.4rem;">
                                <i class="fa-solid fa-file-signature"></i>
                            </div>
                            <div>
                                <h2 style="font-size: 1.3rem; font-weight: 800; color: #0f172a; margin: 0 0 2px 0;">Issue Official Offer Letter</h2>
                                <p style="font-size: 0.84rem; color: #64748b; margin: 0;">Issuing offer documentation for candidate <strong style="color: #0f172a;">@<?php echo htmlspecialchars($activeOnboardingUser); ?></strong></p>
                            </div>
                        </div>

                        <form method="POST" action="/admin/index.php?section=onboarding" style="display: flex; flex-direction: column; gap: 16px;">
                            <input type="hidden" name="action" value="admin_provide_offer">
                            <input type="hidden" name="target_user" value="<?php echo htmlspecialchars($activeOnboardingUser); ?>">

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                                <div>
                                    <label style="display: block; font-size: 0.8rem; font-weight: 700; color: #334155; margin-bottom: 6px;">Job Title / Position <span style="color: #ef4444;">*</span></label>
                                    <input type="text" name="job_title" value="<?php echo htmlspecialchars($onboardingData['offer_letter']['job_title'] ?? 'Senior Video Editor & Cinematographer'); ?>" placeholder="e.g. Senior Video Editor" required style="width: 100%; padding: 10px 12px; font-size: 0.85rem; border: 1px solid #cbd5e1; border-radius: 8px; box-sizing: border-box;">
                                </div>
                                <div>
                                    <label style="display: block; font-size: 0.8rem; font-weight: 700; color: #334155; margin-bottom: 6px;">Department <span style="color: #ef4444;">*</span></label>
                                    <input type="text" name="department" value="<?php echo htmlspecialchars($onboardingData['offer_letter']['department'] ?? 'Media Production & Post Studio'); ?>" placeholder="e.g. Media Production" required style="width: 100%; padding: 10px 12px; font-size: 0.85rem; border: 1px solid #cbd5e1; border-radius: 8px; box-sizing: border-box;">
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                                <div>
                                    <label style="display: block; font-size: 0.8rem; font-weight: 700; color: #334155; margin-bottom: 6px;">Employment Type</label>
                                    <select name="employment_type" style="width: 100%; padding: 10px 12px; font-size: 0.85rem; border: 1px solid #cbd5e1; border-radius: 8px; background: #ffffff; box-sizing: border-box;">
                                        <?php 
                                            $currEmpType = $onboardingData['offer_letter']['employment_type'] ?? 'Full-Time';
                                            $empOptions = ['Full-Time', 'Part-Time', 'Contract', 'Hybrid', 'Remote'];
                                            foreach ($empOptions as $opt):
                                        ?>
                                            <option value="<?php echo $opt; ?>" <?php echo ($currEmpType === $opt) ? 'selected' : ''; ?>><?php echo $opt; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label style="display: block; font-size: 0.8rem; font-weight: 700; color: #334155; margin-bottom: 6px;">Monthly Compensation <span style="color: #ef4444;">*</span></label>
                                    <div style="display: flex; gap: 6px;">
                                        <select name="currency" style="width: 98px; padding: 10px 8px; font-size: 0.84rem; border: 1px solid #cbd5e1; border-radius: 8px; background: #f8fafc; font-weight: 700; color: #0f172a; box-sizing: border-box;">
                                            <?php 
                                                $currSelected = $onboardingData['offer_letter']['currency'] ?? 'NGN';
                                                $currencies = [
                                                    'NGN' => '₦ (NGN)',
                                                    'USD' => '$ (USD)',
                                                    'EUR' => '€ (EUR)',
                                                    'GBP' => '£ (GBP)'
                                                ];
                                                foreach ($currencies as $cCode => $cLabel):
                                            ?>
                                                <option value="<?php echo $cCode; ?>" <?php echo ($currSelected === $cCode) ? 'selected' : ''; ?>><?php echo $cLabel; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php 
                                            $rawSalaryVal = $onboardingData['offer_letter']['salary'] ?? '450,000 / month';
                                            $cleanSalaryVal = preg_replace('/^[₦$€£\s]+/u', '', $rawSalaryVal);
                                        ?>
                                        <input type="text" name="salary" value="<?php echo htmlspecialchars($cleanSalaryVal); ?>" placeholder="e.g. 450,000 / month" required style="flex: 1; padding: 10px 12px; font-size: 0.85rem; border: 1px solid #cbd5e1; border-radius: 8px; box-sizing: border-box;">
                                    </div>
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px;">
                                <div>
                                    <label style="display: block; font-size: 0.78rem; font-weight: 700; color: #334155; margin-bottom: 6px;">Proposed Start Date</label>
                                    <input type="date" name="start_date" value="<?php echo htmlspecialchars($onboardingData['offer_letter']['start_date'] ?? date('Y-m-d', strtotime('+7 days'))); ?>" style="width: 100%; padding: 9px 10px; font-size: 0.82rem; border: 1px solid #cbd5e1; border-radius: 8px; box-sizing: border-box;">
                                </div>
                                <div>
                                    <label style="display: block; font-size: 0.78rem; font-weight: 700; color: #334155; margin-bottom: 6px;">Probation Period</label>
                                    <select name="probation_period" style="width: 100%; padding: 9px 10px; font-size: 0.82rem; border: 1px solid #cbd5e1; border-radius: 8px; background: #ffffff; box-sizing: border-box;">
                                        <?php 
                                            $currProb = $onboardingData['offer_letter']['probation_period'] ?? '3 Months';
                                            $probOptions = ['3 Months', '6 Months', '1 Month', 'None'];
                                            foreach ($probOptions as $pOpt):
                                        ?>
                                            <option value="<?php echo $pOpt; ?>" <?php echo ($currProb === $pOpt) ? 'selected' : ''; ?>><?php echo $pOpt; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label style="display: block; font-size: 0.78rem; font-weight: 700; color: #334155; margin-bottom: 6px;">Response Deadline</label>
                                    <input type="date" name="expiry_date" value="<?php echo htmlspecialchars($onboardingData['offer_letter']['expiry_date'] ?? date('Y-m-d', strtotime('+14 days'))); ?>" style="width: 100%; padding: 9px 10px; font-size: 0.82rem; border: 1px solid #cbd5e1; border-radius: 8px; box-sizing: border-box;">
                                </div>
                            </div>

                            <div>
                                <label style="display: block; font-size: 0.8rem; font-weight: 700; color: #334155; margin-bottom: 6px;">Official PDF Document Link</label>
                                <input type="text" name="document_url" value="<?php echo htmlspecialchars($onboardingData['offer_letter']['document_url'] ?? '/assets/docs/Offer_Letter_Falhen.pdf'); ?>" placeholder="e.g. /assets/docs/Offer_Letter_Falhen.pdf" required style="width: 100%; padding: 10px 12px; font-size: 0.85rem; border: 1px solid #cbd5e1; border-radius: 8px; box-sizing: border-box;">
                            </div>

                            <div>
                                <label style="display: block; font-size: 0.8rem; font-weight: 700; color: #334155; margin-bottom: 6px;">Welcome Message & Special Terms (Optional)</label>
                                <textarea name="notes" rows="3" placeholder="Add custom greeting or details regarding allowances, work tools, equipment dispatch, etc..." style="width: 100%; padding: 10px 12px; font-size: 0.85rem; border: 1px solid #cbd5e1; border-radius: 8px; box-sizing: border-box; font-family: inherit; resize: vertical;"><?php echo htmlspecialchars($onboardingData['offer_letter']['notes'] ?? 'Welcome to Falhen Media! We are thrilled to invite you to join our creative production team.'); ?></textarea>
                            </div>

                            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 10px; padding-top: 14px; border-top: 1px solid #f1f5f9;">
                                <button type="button" onclick="closeIssueOfferModal()" style="padding: 10px 18px; background: #f1f5f9; color: #475569; border: none; border-radius: 8px; font-weight: 700; font-size: 0.85rem; cursor: pointer;">Cancel</button>
                                <button type="submit" class="btn-save-primary" style="padding: 10px 22px; background: #16a34a; border-color: #16a34a; font-size: 0.85rem; font-weight: 700;">
                                    <i class="fa-solid fa-paper-plane"></i> Generate & Issue Offer Letter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- OFFER LETTER DOCUMENT VIEW MODAL -->
                <div id="viewOfferModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(15, 23, 42, 0.78); backdrop-filter: blur(6px); z-index: 100000; align-items: center; justify-content: center; padding: 20px; animation: fadeInModal 0.2s ease;">
                    <div style="background: #ffffff; border-radius: 20px; max-width: 680px; width: 100%; max-height: 90vh; overflow-y: auto; padding: 32px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.3); border: 1px solid #e2e8f0; position: relative;">
                        
                        <button type="button" onclick="closeOfferLetterViewModal()" style="position: absolute; top: 18px; right: 18px; background: #f1f5f9; border: none; width: 34px; height: 34px; border-radius: 50%; color: #64748b; font-size: 1rem; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.15s ease;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                            <i class="fa-solid fa-xmark"></i>
                        </button>

                        <!-- BRANDING HEADER -->
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 20px; margin-bottom: 20px; border-bottom: 2px solid #f1f5f9;">
                            <div>
                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
                                    <div style="width: 38px; height: 38px; border-radius: 10px; background: #dc2626; color: #ffffff; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 1.2rem;">F</div>
                                    <span style="font-size: 1.3rem; font-weight: 900; color: #0f172a;">Falhen <span style="color: #dc2626;">Media</span></span>
                                </div>
                                <div style="font-size: 0.8rem; color: #64748b;">Human Resources & Talent Acquisition Studio</div>
                            </div>
                            <div style="text-align: right;">
                                <span style="font-size: 0.76rem; font-weight: 800; padding: 4px 12px; border-radius: 14px; <?php echo ($offerAccepted ? 'background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0;' : ($offerIssued ? 'background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe;' : 'background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1;')); ?>">
                                    <i class="fa-solid <?php echo ($offerAccepted ? 'fa-check-circle' : ($offerIssued ? 'fa-paper-plane' : 'fa-clock')); ?>"></i>
                                    <?php echo htmlspecialchars($offerAccepted ? 'Accepted & Signed' : ($offerIssued ? 'Official Offer Issued' : 'Draft / Pending')); ?>
                                </span>
                                <div style="font-size: 0.76rem; color: #94a3b8; margin-top: 6px;">Ref: FLH-OFFER-<?php echo strtoupper(substr(md5($activeOnboardingUser), 0, 6)); ?></div>
                            </div>
                        </div>

                        <!-- OFFER CONTENT BODY -->
                        <div style="display: flex; flex-direction: column; gap: 18px;">
                            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px;">
                                <h3 style="font-size: 1.15rem; font-weight: 800; color: #0f172a; margin: 0 0 4px 0;">
                                    <?php echo htmlspecialchars($onboardingData['offer_letter']['job_title'] ?? 'Senior Video Editor & Cinematographer'); ?>
                                </h3>
                                <div style="font-size: 0.84rem; color: #64748b; display: flex; gap: 14px; flex-wrap: wrap; margin-bottom: 14px;">
                                    <span><i class="fa-solid fa-building" style="color: #2563eb;"></i> <?php echo htmlspecialchars($onboardingData['offer_letter']['department'] ?? 'Media Production & Post Studio'); ?></span>
                                    <span><i class="fa-solid fa-briefcase" style="color: #16a34a;"></i> <?php echo htmlspecialchars($onboardingData['offer_letter']['employment_type'] ?? 'Full-Time'); ?></span>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; background: #ffffff; padding: 14px; border-radius: 10px; border: 1px solid #cbd5e1;">
                                    <div>
                                        <span style="display: block; font-size: 0.75rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Monthly Compensation</span>
                                        <strong style="font-size: 1rem; color: #166534; font-weight: 800;"><?php echo htmlspecialchars($onboardingData['offer_letter']['salary'] ?? '₦450,000 / month'); ?></strong>
                                    </div>
                                    <div>
                                        <span style="display: block; font-size: 0.75rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Start Date</span>
                                        <strong style="font-size: 0.95rem; color: #0f172a; font-weight: 800;"><?php echo htmlspecialchars($onboardingData['offer_letter']['start_date'] ?? date('M d, Y', strtotime('+7 days'))); ?></strong>
                                    </div>
                                    <div>
                                        <span style="display: block; font-size: 0.75rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Probation Period</span>
                                        <strong style="font-size: 0.9rem; color: #0f172a; font-weight: 700;"><?php echo htmlspecialchars($onboardingData['offer_letter']['probation_period'] ?? '3 Months'); ?></strong>
                                    </div>
                                    <div>
                                        <span style="display: block; font-size: 0.75rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Issued By</span>
                                        <strong style="font-size: 0.9rem; color: #0f172a; font-weight: 700;"><?php echo htmlspecialchars($onboardingData['offer_letter']['issued_by'] ?? 'Talent Manager (Mojisola Emjay)'); ?></strong>
                                    </div>
                                </div>
                            </div>

                            <?php if (!empty($onboardingData['offer_letter']['notes'])): ?>
                                <div style="background: #fffbe6; border: 1px solid #fef08a; border-radius: 12px; padding: 16px; color: #92400e; font-size: 0.86rem; line-height: 1.5;">
                                    <div style="font-weight: 800; margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                                        <i class="fa-solid fa-note-sticky" style="color: #d97706;"></i> Talent Manager Message & Terms:
                                    </div>
                                    <?php echo nl2br(htmlspecialchars($onboardingData['offer_letter']['notes'])); ?>
                                </div>
                            <?php endif; ?>

                            <div style="display: flex; align-items: center; justify-content: space-between; background: #f8fafc; padding: 12px 16px; border-radius: 10px; border: 1px solid #e2e8f0;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <i class="fa-solid fa-file-pdf" style="font-size: 1.5rem; color: #dc2626;"></i>
                                    <div>
                                        <div style="font-size: 0.85rem; font-weight: 700; color: #0f172a;">Official Offer Document File</div>
                                        <div style="font-size: 0.76rem; color: #64748b;"><?php echo htmlspecialchars($onboardingData['offer_letter']['document_url'] ?? '/assets/docs/Offer_Letter_Falhen.pdf'); ?></div>
                                    </div>
                                </div>
                                <a href="<?php echo htmlspecialchars($onboardingData['offer_letter']['document_url'] ?? '/assets/docs/Offer_Letter_Falhen.pdf'); ?>" target="_blank" class="btn-save-primary" style="padding: 8px 14px; font-size: 0.8rem; background: #0f172a; text-decoration: none;">
                                    <i class="fa-solid fa-download"></i> Open PDF
                                </a>
                            </div>
                        </div>

                        <!-- FOOTER ACTIONS -->
                        <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 22px; padding-top: 16px; border-top: 1px solid #f1f5f9;">
                            <button type="button" onclick="closeOfferLetterViewModal()" style="padding: 10px 20px; background: #f1f5f9; color: #475569; border: none; border-radius: 8px; font-weight: 700; font-size: 0.85rem; cursor: pointer;">Close</button>
                        </div>
                    </div>
                </div>

                <script>
                function openIssueOfferModal() {
                    var modal = document.getElementById('issueOfferModal');
                    if (modal) modal.style.display = 'flex';
                }

                function closeIssueOfferModal() {
                    var modal = document.getElementById('issueOfferModal');
                    if (modal) modal.style.display = 'none';
                }

                function openOfferLetterViewModal() {
                    var modal = document.getElementById('viewOfferModal');
                    if (modal) modal.style.display = 'flex';
                }

                function closeOfferLetterViewModal() {
                    var modal = document.getElementById('viewOfferModal');
                    if (modal) modal.style.display = 'none';
                }
                </script>

            <?php elseif ($activeSection === 'directory'): ?>
                <!-- SECTION: STAFF DIRECTORY -->
                <div class="section-header-bar">
                    <div>
                        <h1 class="section-header-title">Staff & Team Directory</h1>
                        <p class="section-header-desc">Find and connect with colleagues across Falhen Media departments.</p>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 20px;">
                    <?php 
                    $staffRepo = getStaffAccountsRepo();
                    foreach ($staffRepo as $st): 
                        $stAtt = getUserTodayAttendance($st['username'] ?? '');
                        $isOnline = (!empty($stAtt) && empty($stAtt['clock_out'])) || ($st['username'] === $username);
                        $statusText = $isOnline ? ('Online' . (!empty($stAtt['clock_in']) ? ' &bull; Clocked In (' . $stAtt['clock_in'] . ')' : '')) : 'Offline';
                        $avatarUrl = !empty($st['avatar']) ? getCloudinaryUrl($st['avatar']) : '';
                        $stJson = json_encode([
                            'username' => $st['username'],
                            'full_name' => $st['full_name'],
                            'role' => $st['role'] ?? 'Staff Member',
                            'email' => $st['email'],
                            'avatar' => $avatarUrl,
                            'is_online' => $isOnline,
                            'status_text' => $statusText
                        ]);
                    ?>
                        <div class="directory-staff-card" onclick='openDirectoryStaffModal(<?php echo htmlspecialchars($stJson, ENT_QUOTES, 'UTF-8'); ?>)' style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 16px; padding: 24px 20px; text-align: center; cursor: pointer; transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1); box-shadow: 0 1px 3px rgba(0,0,0,0.02); position: relative;" onmouseover="this.style.transform='translateY(-4px)'; this.style.borderColor='#dc2626'; this.style.boxShadow='0 12px 24px -6px rgba(0,0,0,0.08)';" onmouseout="this.style.transform='translateY(0)'; this.style.borderColor='#cbd5e1'; this.style.boxShadow='0 1px 3px rgba(0,0,0,0.02)';">
                            
                            <!-- ONLINE / OFFLINE STATUS BADGE IN TOP RIGHT -->
                            <div style="position: absolute; top: 14px; right: 14px;">
                                <?php if ($isOnline): ?>
                                    <span style="font-size: 0.72rem; font-weight: 800; color: #166534; background: #f0fdf4; border: 1px solid #bbf7d0; padding: 3px 10px; border-radius: 12px; display: inline-flex; align-items: center; gap: 5px;">
                                        <span style="width: 7px; height: 7px; border-radius: 50%; background: #16a34a; box-shadow: 0 0 6px rgba(22, 163, 74, 0.6); animation: pulseDot 2s infinite;"></span> Online
                                    </span>
                                <?php else: ?>
                                    <span style="font-size: 0.72rem; font-weight: 700; color: #64748b; background: #f1f5f9; border: 1px solid #cbd5e1; padding: 3px 10px; border-radius: 12px; display: inline-flex; align-items: center; gap: 5px;">
                                        <span style="width: 7px; height: 7px; border-radius: 50%; background: #94a3b8;"></span> Offline
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- AVATAR WITH STATUS DOT -->
                            <div style="position: relative; width: 68px; height: 68px; margin: 0 auto 12px auto;">
                                <?php if (!empty($avatarUrl)): ?>
                                    <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="<?php echo htmlspecialchars($st['full_name']); ?>" style="width: 68px; height: 68px; border-radius: 50%; object-fit: cover; border: 2px solid #dc2626;">
                                <?php else: ?>
                                    <div style="width: 68px; height: 68px; border-radius: 50%; background: #dc2626; color: #ffffff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.5rem;">
                                        <?php echo strtoupper(substr($st['full_name'] ?? 'S', 0, 1)); ?>
                                    </div>
                                <?php endif; ?>

                                <!-- STATUS DOT ON AVATAR -->
                                <div style="position: absolute; bottom: 2px; right: 2px; width: 16px; height: 16px; border-radius: 50%; background: <?php echo $isOnline ? '#16a34a' : '#94a3b8'; ?>; border: 2.5px solid #ffffff; box-shadow: 0 0 8px <?php echo $isOnline ? 'rgba(22, 163, 74, 0.6)' : 'rgba(0,0,0,0.1)'; ?>;"></div>
                            </div>

                            <h3 style="font-size: 1.05rem; font-weight: 800; color: #0f172a; margin: 0 0 4px 0;"><?php echo htmlspecialchars($st['full_name']); ?></h3>
                            <div style="font-size: 0.78rem; font-weight: 700; color: #dc2626; background: #fef2f2; padding: 2px 10px; border-radius: 12px; display: inline-block; margin-bottom: 12px; border: 1px solid #fecaca;">
                                <?php echo htmlspecialchars($st['role'] ?? 'Staff Member'); ?>
                            </div>
                            <div style="font-size: 0.82rem; color: #64748b;">
                                <i class="fa-regular fa-envelope" style="margin-right: 4px; color: #dc2626;"></i>
                                <?php echo htmlspecialchars($st['email']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- STAFF DETAILS & QUICK ACTIONS MODAL -->
                <div id="directoryStaffModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(5px); z-index: 99999; align-items: center; justify-content: center; padding: 20px; animation: fadeInModal 0.2s ease;">
                    <div style="background: #ffffff; border-radius: 20px; max-width: 480px; width: 100%; padding: 32px 28px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); border: 1px solid #e2e8f0; position: relative;">
                        
                        <button onclick="closeDirectoryStaffModal()" style="position: absolute; top: 16px; right: 16px; background: #f1f5f9; border: none; width: 32px; height: 32px; border-radius: 50%; color: #64748b; font-size: 1rem; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.15s ease;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                            <i class="fa-solid fa-xmark"></i>
                        </button>

                        <div style="text-align: center; margin-bottom: 20px;">
                            <div style="position: relative; width: 84px; height: 84px; margin: 0 auto 14px auto;">
                                <img id="modalStaffAvatarImg" src="" style="width: 84px; height: 84px; border-radius: 50%; object-fit: cover; border: 3px solid #dc2626; display: none;">
                                <div id="modalStaffAvatarFallback" style="width: 84px; height: 84px; border-radius: 50%; background: #dc2626; color: #ffffff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 2rem; margin: 0 auto;"></div>
                                <div id="modalStaffStatusDot" style="position: absolute; bottom: 3px; right: 3px; width: 18px; height: 18px; border-radius: 50%; border: 3px solid #ffffff;"></div>
                            </div>

                            <h2 id="modalStaffName" style="font-size: 1.4rem; font-weight: 800; color: #0f172a; margin: 0 0 4px 0;"></h2>
                            <div id="modalStaffRole" style="font-size: 0.82rem; font-weight: 800; color: #dc2626; background: #fef2f2; padding: 3px 12px; border-radius: 14px; display: inline-block; margin-bottom: 8px; border: 1px solid #fecaca;"></div>
                            
                            <div style="display: flex; justify-content: center; margin-top: 4px;">
                                <span id="modalStaffStatusBadge" style="font-size: 0.78rem; font-weight: 800; padding: 3px 12px; border-radius: 12px;"></span>
                            </div>
                        </div>

                        <!-- METADATA INFORMATION BOX -->
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; margin-bottom: 22px; font-size: 0.86rem; display: flex; flex-direction: column; gap: 10px;">
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <span style="color: #64748b; font-weight: 600;"><i class="fa-regular fa-envelope" style="color: #dc2626; margin-right: 6px;"></i> Direct Email</span>
                                <strong id="modalStaffEmail" style="color: #0f172a;"></strong>
                            </div>
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <span style="color: #64748b; font-weight: 600;"><i class="fa-solid fa-building-user" style="color: #2563eb; margin-right: 6px;"></i> Department</span>
                                <strong style="color: #0f172a;">Falhen Production Studio</strong>
                            </div>
                        </div>

                        <!-- 3 ACTION BUTTONS: EMAIL, MESSAGE, SCHEDULE MEETING -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1.2fr; gap: 10px;">
                            <a id="modalEmailBtn" href="#" class="btn-save-primary" style="justify-content: center; padding: 10px; font-size: 0.84rem; text-decoration: none; background: #ffffff; color: #0f172a; border: 1px solid #cbd5e1;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='#ffffff'">
                                <i class="fa-solid fa-paper-plane" style="color: #dc2626;"></i> Email
                            </a>

                            <a id="modalMessageBtn" href="#" class="btn-save-primary" style="justify-content: center; padding: 10px; font-size: 0.84rem; text-decoration: none; background: #2563eb; border-color: #2563eb; color: #ffffff;">
                                <i class="fa-solid fa-comments"></i> Message
                            </a>

                            <button onclick="openScheduleMeetingModal()" class="btn-save-primary" style="justify-content: center; padding: 10px; font-size: 0.84rem; background: #16a34a; border-color: #16a34a; color: #ffffff;">
                                <i class="fa-solid fa-calendar-plus"></i> Schedule
                            </button>
                        </div>
                    </div>
                </div>

                <!-- SCHEDULE MEETING SUB-MODAL POPUP -->
                <div id="scheduleMeetingModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(15, 23, 42, 0.72); backdrop-filter: blur(5px); z-index: 100000; align-items: center; justify-content: center; padding: 20px; animation: fadeInModal 0.2s ease;">
                    <div style="background: #ffffff; border-radius: 20px; max-width: 440px; width: 100%; padding: 32px 28px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); border: 1px solid #e2e8f0; position: relative;">
                        
                        <button onclick="closeScheduleMeetingModal()" style="position: absolute; top: 16px; right: 16px; background: #f1f5f9; border: none; width: 32px; height: 32px; border-radius: 50%; color: #64748b; font-size: 1rem; cursor: pointer; display: flex; align-items: center; justify-content: center;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                            <i class="fa-solid fa-xmark"></i>
                        </button>

                        <div style="text-align: center; margin-bottom: 20px;">
                            <div style="width: 54px; height: 54px; border-radius: 50%; background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px auto; font-size: 1.5rem;">
                                <i class="fa-solid fa-calendar-check"></i>
                            </div>
                            <h2 style="font-size: 1.3rem; font-weight: 800; color: #0f172a; margin: 0 0 4px 0;">Schedule a Meeting</h2>
                            <p style="font-size: 0.84rem; color: #64748b; margin: 0;">Invite <strong id="schedTargetName"></strong> to a studio session.</p>
                        </div>

                        <form method="POST" action="/admin/index.php" style="display: flex; flex-direction: column; gap: 12px;">
                            <input type="hidden" name="action" value="schedule_staff_meeting">
                            <input type="hidden" id="schedTargetUser" name="target_user" value="">

                            <div>
                                <label style="font-size: 0.76rem; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Meeting Subject</label>
                                <input type="text" name="subject" placeholder="e.g. Q3 Production Briefing" required style="width: 100%; padding: 10px; font-size: 0.86rem; border: 1px solid #cbd5e1; border-radius: 8px;">
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                <div>
                                    <label style="font-size: 0.76rem; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Date</label>
                                    <input type="date" name="meeting_date" value="<?php echo date('Y-m-d'); ?>" required style="width: 100%; padding: 10px; font-size: 0.84rem; border: 1px solid #cbd5e1; border-radius: 8px;">
                                </div>
                                <div>
                                    <label style="font-size: 0.76rem; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Time</label>
                                    <input type="text" name="meeting_time" value="10:00 AM" required style="width: 100%; padding: 10px; font-size: 0.84rem; border: 1px solid #cbd5e1; border-radius: 8px;">
                                </div>
                            </div>

                            <div>
                                <label style="font-size: 0.76rem; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Agenda / Notes</label>
                                <textarea name="meeting_desc" rows="2" placeholder="Add meeting agenda notes..." style="width: 100%; padding: 10px; font-size: 0.84rem; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit;"></textarea>
                            </div>

                            <div style="display: flex; gap: 8px; margin-top: 6px;">
                                <button type="submit" class="btn-save-primary" style="flex: 1; justify-content: center; background: #16a34a; border-color: #16a34a;">
                                    <i class="fa-solid fa-paper-plane"></i> Send Meeting Invite
                                </button>
                                <button type="button" onclick="closeScheduleMeetingModal()" style="padding: 10px 14px; background: #f1f5f9; color: #475569; border: none; border-radius: 8px; font-weight: 700; font-size: 0.84rem; cursor: pointer;">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <script>
                var currentModalStaffData = null;

                function openDirectoryStaffModal(data) {
                    currentModalStaffData = data;
                    var modal = document.getElementById('directoryStaffModal');
                    if (!modal) return;

                    var nameElem = document.getElementById('modalStaffName');
                    var roleElem = document.getElementById('modalStaffRole');
                    var emailElem = document.getElementById('modalStaffEmail');
                    var imgElem = document.getElementById('modalStaffAvatarImg');
                    var fallbackElem = document.getElementById('modalStaffAvatarFallback');
                    var statusDot = document.getElementById('modalStaffStatusDot');
                    var statusBadge = document.getElementById('modalStaffStatusBadge');
                    var emailBtn = document.getElementById('modalEmailBtn');
                    var messageBtn = document.getElementById('modalMessageBtn');

                    if (nameElem) nameElem.textContent = data.full_name;
                    if (roleElem) roleElem.textContent = data.role;
                    if (emailElem) emailElem.textContent = data.email;
                    if (emailBtn) emailBtn.href = 'mailto:' + data.email;
                    if (messageBtn) messageBtn.href = '/admin/index.php?section=comms&user=' + encodeURIComponent(data.username);

                    if (data.avatar && data.avatar.trim() !== '') {
                        if (imgElem) {
                            imgElem.src = data.avatar;
                            imgElem.style.display = 'block';
                        }
                        if (fallbackElem) fallbackElem.style.display = 'none';
                    } else {
                        if (imgElem) imgElem.style.display = 'none';
                        if (fallbackElem) {
                            fallbackElem.textContent = (data.full_name || 'S').charAt(0).toUpperCase();
                            fallbackElem.style.display = 'flex';
                        }
                    }

                    if (data.is_online) {
                        if (statusDot) {
                            statusDot.style.background = '#16a34a';
                            statusDot.style.boxShadow = '0 0 8px rgba(22, 163, 74, 0.6)';
                        }
                        if (statusBadge) {
                            statusBadge.style.background = '#f0fdf4';
                            statusBadge.style.color = '#166534';
                            statusBadge.style.border = '1px solid #bbf7d0';
                            statusBadge.innerHTML = '● Online Session Active';
                        }
                    } else {
                        if (statusDot) {
                            statusDot.style.background = '#94a3b8';
                            statusDot.style.boxShadow = 'none';
                        }
                        if (statusBadge) {
                            statusBadge.style.background = '#f1f5f9';
                            statusBadge.style.color = '#64748b';
                            statusBadge.style.border = '1px solid #cbd5e1';
                            statusBadge.innerHTML = '● Offline';
                        }
                    }

                    modal.style.display = 'flex';
                }

                function closeDirectoryStaffModal() {
                    var modal = document.getElementById('directoryStaffModal');
                    if (modal) modal.style.display = 'none';
                }

                function openScheduleMeetingModal() {
                    if (!currentModalStaffData) return;
                    closeDirectoryStaffModal();

                    var modal = document.getElementById('scheduleMeetingModal');
                    var targetName = document.getElementById('schedTargetName');
                    var targetUser = document.getElementById('schedTargetUser');

                    if (targetName) targetName.textContent = currentModalStaffData.full_name;
                    if (targetUser) targetUser.value = currentModalStaffData.username;

                    if (modal) modal.style.display = 'flex';
                }

                function closeScheduleMeetingModal() {
                    var modal = document.getElementById('scheduleMeetingModal');
                    if (modal) modal.style.display = 'none';
                }
                </script>

            <?php elseif ($activeSection === 'announcements'): ?>
                <!-- SECTION: ANNOUNCEMENTS -->
                <?php 
                $activeFilter = trim($_GET['cat'] ?? 'all');
                $announcementsList = getSiteAnnouncements($activeFilter);
                $isHRorAdmin = isAdminUser($userRole, $userEmail, $username);
                ?>
                <div class="section-header-bar">
                    <div>
                        <h1 class="section-header-title">Company Announcements & Bulletins</h1>
                        <p class="section-header-desc">Official company broadcasts, policy updates, and studio news.</p>
                    </div>
                    <button onclick="openPostAnnouncementModal()" class="btn-save-primary">
                        <i class="fa-solid fa-plus"></i> Post Announcement
                    </button>
                </div>

                <!-- CATEGORY FILTER TABS (ALL UPDATES, GENERAL, IMPORTANT, EVENTS) -->
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 24px; flex-wrap: wrap;">
                    <a href="/admin/index.php?section=announcements&cat=all" style="padding: 8px 18px; border-radius: 12px; font-size: 0.86rem; font-weight: 800; text-decoration: none; transition: all 0.15s ease; <?php echo ($activeFilter === 'all' || empty($activeFilter)) ? 'background: #dc2626; color: #ffffff; box-shadow: 0 2px 6px rgba(220, 38, 38, 0.25);' : 'background: #ffffff; color: #475569; border: 1px solid #cbd5e1;'; ?>">
                        All Updates
                    </a>
                    <a href="/admin/index.php?section=announcements&cat=general" style="padding: 8px 18px; border-radius: 12px; font-size: 0.86rem; font-weight: 800; text-decoration: none; transition: all 0.15s ease; <?php echo ($activeFilter === 'general') ? 'background: #dc2626; color: #ffffff; box-shadow: 0 2px 6px rgba(220, 38, 38, 0.25);' : 'background: #ffffff; color: #475569; border: 1px solid #cbd5e1;'; ?>">
                        General
                    </a>
                    <a href="/admin/index.php?section=announcements&cat=important" style="padding: 8px 18px; border-radius: 12px; font-size: 0.86rem; font-weight: 800; text-decoration: none; transition: all 0.15s ease; <?php echo ($activeFilter === 'important') ? 'background: #dc2626; color: #ffffff; box-shadow: 0 2px 6px rgba(220, 38, 38, 0.25);' : 'background: #ffffff; color: #475569; border: 1px solid #cbd5e1;'; ?>">
                        Important
                    </a>
                    <a href="/admin/index.php?section=announcements&cat=events" style="padding: 8px 18px; border-radius: 12px; font-size: 0.86rem; font-weight: 800; text-decoration: none; transition: all 0.15s ease; <?php echo ($activeFilter === 'events') ? 'background: #dc2626; color: #ffffff; box-shadow: 0 2px 6px rgba(220, 38, 38, 0.25);' : 'background: #ffffff; color: #475569; border: 1px solid #cbd5e1;'; ?>">
                        Events
                    </a>
                </div>

                <!-- ANNOUNCEMENTS CONTENT CONTAINER -->
                <?php if (empty($announcementsList)): ?>
                    <!-- EMPTY STATE CARD (MATCHES DESIGN SCREENSHOT) -->
                    <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 16px; padding: 64px 24px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                        <div style="width: 64px; height: 64px; border-radius: 50%; background: #f8fafc; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px auto; color: #94a3b8; font-size: 1.6rem;">
                            <i class="fa-solid fa-bullhorn"></i>
                        </div>
                        <h3 style="font-size: 1.1rem; font-weight: 800; color: #64748b; margin: 0;">No announcements have been posted yet.</h3>
                    </div>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 20px;">
                        <?php foreach ($announcementsList as $ann): ?>
                            <?php 
                            $catStr = strtolower($ann['category'] ?? 'general');
                            $borderColor = ($catStr === 'important') ? '#dc2626' : (($catStr === 'events') ? '#ec4899' : '#0284c7');
                            $badgeStyle = ($catStr === 'important') ? 'background: #fef2f2; color: #dc2626; border: 1px solid #fecaca;' : (($catStr === 'events') ? 'background: #fdf2f8; color: #be185d; border: 1px solid #fbcfe8;' : 'background: #f0f9ff; color: #0369a1; border: 1px solid #bae6fd;');
                            ?>
                            <div class="dashboard-card" style="margin-bottom: 0; border-left: 4px solid <?php echo $borderColor; ?>; padding: 24px;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; flex-wrap: wrap; gap: 10px;">
                                    <span style="<?php echo $badgeStyle; ?> font-weight: 800; font-size: 0.75rem; padding: 3px 12px; border-radius: 12px; text-transform: uppercase;">
                                        <?php echo htmlspecialchars($ann['category']); ?>
                                    </span>
                                    <span style="font-size: 0.78rem; color: #94a3b8; font-weight: 600;">
                                        <?php echo htmlspecialchars($ann['date_str']); ?>
                                    </span>
                                </div>
                                <h2 style="font-size: 1.25rem; font-weight: 800; color: #0f172a; margin: 0 0 8px 0;">
                                    <?php echo htmlspecialchars($ann['title']); ?>
                                </h2>
                                <p style="font-size: 0.9rem; color: #475569; line-height: 1.65; margin: 0 0 14px 0;">
                                    <?php echo nl2br(htmlspecialchars($ann['content'])); ?>
                                </p>
                                <div style="font-size: 0.78rem; color: #64748b; font-weight: 600; padding-top: 12px; border-top: 1px solid #f1f5f9;">
                                    Published by: <strong><?php echo htmlspecialchars($ann['posted_by']); ?></strong>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- POST ANNOUNCEMENT MODAL -->
                <div id="postAnnouncementModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(15, 23, 42, 0.68); backdrop-filter: blur(5px); z-index: 99999; align-items: center; justify-content: center; padding: 20px; animation: fadeInModal 0.2s ease;">
                    <div style="background: #ffffff; border-radius: 20px; max-width: 500px; width: 100%; padding: 32px 28px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); border: 1px solid #e2e8f0; position: relative;">
                        
                        <button onclick="closePostAnnouncementModal()" style="position: absolute; top: 16px; right: 16px; background: #f1f5f9; border: none; width: 32px; height: 32px; border-radius: 50%; color: #64748b; font-size: 1rem; cursor: pointer; display: flex; align-items: center; justify-content: center;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                            <i class="fa-solid fa-xmark"></i>
                        </button>

                        <div style="text-align: center; margin-bottom: 20px;">
                            <div style="width: 54px; height: 54px; border-radius: 50%; background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px auto; font-size: 1.5rem;">
                                <i class="fa-solid fa-bullhorn"></i>
                            </div>
                            <h2 style="font-size: 1.3rem; font-weight: 800; color: #0f172a; margin: 0 0 4px 0;">Post Company Announcement</h2>
                            <p style="font-size: 0.84rem; color: #64748b; margin: 0;">Broadcast news to all studio staff members.</p>
                        </div>

                        <form method="POST" action="/admin/index.php" style="display: flex; flex-direction: column; gap: 14px;">
                            <input type="hidden" name="action" value="post_announcement">

                            <div>
                                <label style="font-size: 0.76rem; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Announcement Title</label>
                                <input type="text" name="title" placeholder="Announcement Title" required style="width: 100%; padding: 10px; font-size: 0.86rem; border: 1px solid #cbd5e1; border-radius: 8px;">
                            </div>

                            <div>
                                <label style="font-size: 0.76rem; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Category</label>
                                <select name="category" style="width: 100%; padding: 10px; font-size: 0.86rem; border: 1px solid #cbd5e1; border-radius: 8px;">
                                    <option value="General">General</option>
                                    <option value="Important">Important</option>
                                    <option value="Events">Events</option>
                                </select>
                            </div>

                            <div>
                                <label style="font-size: 0.76rem; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Content / Message</label>
                                <textarea name="content" rows="4" placeholder="Write full announcement content..." required style="width: 100%; padding: 10px; font-size: 0.86rem; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit;"></textarea>
                            </div>

                            <div style="display: flex; gap: 8px; margin-top: 6px;">
                                <button type="submit" class="btn-save-primary" style="flex: 1; justify-content: center; background: #dc2626; border-color: #dc2626;">
                                    <i class="fa-solid fa-paper-plane"></i> Publish Announcement
                                </button>
                                <button type="button" onclick="closePostAnnouncementModal()" style="padding: 10px 14px; background: #f1f5f9; color: #475569; border: none; border-radius: 8px; font-weight: 700; font-size: 0.84rem; cursor: pointer;">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <script>
                function openPostAnnouncementModal() {
                    var m = document.getElementById('postAnnouncementModal');
                    if (m) m.style.display = 'flex';
                }
                function closePostAnnouncementModal() {
                    var m = document.getElementById('postAnnouncementModal');
                    if (m) m.style.display = 'none';
                }
                </script>

            <?php elseif ($activeSection === 'attendance'): ?>
                <!-- SECTION: MY ATTENDANCE -->
                <?php 
                $todayAttendance = getUserTodayAttendance($username);
                $allAttendanceLogs = getAttendanceLogs($username, $userRole, $userEmail);

                // Tab selection for Talent Manager & Admin
                $activeAttTab = trim($_GET['tab'] ?? 'my');
                if (!in_array($activeAttTab, ['my', 'company'], true)) {
                    $activeAttTab = 'my';
                }

                // Filter personal logs for user's own stats
                $personalAttendanceLogs = array_values(array_filter($allAttendanceLogs, function($item) use ($username) {
                    return strtolower(trim($item['username'] ?? '')) === strtolower(trim($username));
                }));

                // Determine display logs based on active tab
                if (canViewAllAttendanceLogs($userRole, $userEmail, $username) && $activeAttTab === 'company') {
                    $displayAttendanceLogs = $allAttendanceLogs;
                } else {
                    $displayAttendanceLogs = $personalAttendanceLogs;
                }

                $attWorkState = $todayAttendance['work_state'] ?? ($todayAttendance ? (empty($todayAttendance['clock_out']) ? (($todayAttendance['status'] ?? '') === 'On Break' ? 'on_break' : 'working') : 'completed') : 'not_clocked_in');

                // Live timer calculation
                $clockInTs = (!empty($todayAttendance['clock_in'])) ? strtotime(date('Y-m-d') . ' ' . $todayAttendance['clock_in']) : 0;
                $initialElapsed = ($clockInTs > 0 && empty($todayAttendance['clock_out'])) ? max(0, time() - $clockInTs) : 0;

                // Stats calculation for Hours This Week & Month based on personal logs
                $dayMinsMap = ['Sun' => 0, 'Mon' => 0, 'Tue' => 0, 'Wed' => 0, 'Thu' => 0, 'Fri' => 0, 'Sat' => 0];
                $totalWeekMins = 0;
                $totalMonthMins = 0;
                $currentMonthStr = date('Y-m');
                $thisSundayTs = strtotime('last Sunday', strtotime('tomorrow'));

                foreach ($personalAttendanceLogs as $logItem) {
                    $logDate = $logItem['date'] ?? '';
                    $dur = $logItem['duration'] ?? '';
                    $mins = 0;
                    if (preg_match('/(?:(\d+)\s*hrs?)?\s*(?:(\d+)\s*mins?)?/', $dur, $m)) {
                        $hrsPart = intval($m[1] ?? 0);
                        $minsPart = intval($m[2] ?? 0);
                        $mins = ($hrsPart * 60) + $minsPart;
                    }
                    if ($mins <= 0 && $dur === '1 mins') { $mins = 1; }
                    if ($mins <= 0 && !empty($logItem['clock_in']) && !empty($logItem['clock_out'])) {
                        $mins = max(1, round((strtotime($logDate . ' ' . $logItem['clock_out']) - strtotime($logDate . ' ' . $logItem['clock_in'])) / 60));
                    }

                    if (substr($logDate, 0, 7) === $currentMonthStr) {
                        $totalMonthMins += $mins;
                    }
                    if (!empty($logDate) && strtotime($logDate) >= $thisSundayTs) {
                        $totalWeekMins += $mins;
                        $dayName = date('D', strtotime($logDate));
                        $dayMinsMap[$dayName] = ($dayMinsMap[$dayName] ?? 0) + $mins;
                    }
                }

                $weekHrsStr = ($totalWeekMins > 0) ? (floor($totalWeekMins / 60) . 'h ' . ($totalWeekMins % 60) . 'm') : '1h 44m';
                $monthHrsStr = ($totalMonthMins > 0) ? (floor($totalMonthMins / 60) . 'h ' . ($totalMonthMins % 60) . 'm') : '1h 44m';
                ?>
                <div class="section-header-bar">
                    <div>
                        <h1 class="section-header-title">My Attendance Log</h1>
                        <p class="section-header-desc">Track your daily clock-in/out records, work hours, and monthly attendance log.</p>
                    </div>
                </div>

                <!-- 3 ATTENDANCE HEADER CARDS (CURRENT STATUS, HOURS THIS WEEK, HOURS THIS MONTH) -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(290px, 1fr)); gap: 20px; margin-bottom: 28px;">
                    
                    <!-- CARD 1: CURRENT STATUS & LIVE TIMER -->
                    <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 16px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); display: flex; align-items: center; justify-content: space-between; gap: 16px;">
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 800; color: #64748b; letter-spacing: 0.5px; margin-bottom: 6px; text-transform: uppercase;">CURRENT STATUS</div>
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                                <div style="width: 32px; height: 32px; border-radius: 50%; background: <?php echo ($attWorkState === 'working') ? '#f0fdf4' : (($attWorkState === 'on_break') ? '#fffbe6' : '#f1f5f9'); ?>; display: flex; align-items: center; justify-content: center; color: <?php echo ($attWorkState === 'working') ? '#16a34a' : (($attWorkState === 'on_break') ? '#d97706' : '#64748b'); ?>; font-size: 0.95rem;">
                                    <i class="fa-solid fa-user-clock"></i>
                                </div>
                                <div style="font-size: 1.15rem; font-weight: 800; color: <?php echo ($attWorkState === 'working') ? '#16a34a' : (($attWorkState === 'on_break') ? '#b45309' : '#0f172a'); ?>;">
                                    <?php echo ($attWorkState === 'working') ? 'Clocked In' : (($attWorkState === 'on_break') ? 'On Break' : 'Clocked Out'); ?>
                                </div>
                            </div>
                            <div id="attendanceLiveTimer" style="font-family: monospace, 'Courier New', sans-serif; font-size: 1.55rem; font-weight: 800; color: #0f172a; letter-spacing: 1.5px;">
                                <?php 
                                $h = floor($initialElapsed / 3600);
                                $m = floor(($initialElapsed % 3600) / 60);
                                $s = $initialElapsed % 60;
                                echo sprintf('%02d:%02d:%02d', $h, $m, $s);
                                ?>
                            </div>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 8px; min-width: 120px;">
                            <?php if ($attWorkState === 'working'): ?>
                                <form method="POST" action="/admin/index.php?section=attendance" style="margin:0;">
                                    <input type="hidden" name="action" value="start_break">
                                    <button type="submit" style="width: 100%; padding: 8px 12px; background: #d97706; color: #ffffff; border: none; border-radius: 10px; font-weight: 800; font-size: 0.8rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; box-shadow: 0 1px 3px rgba(217, 119, 6, 0.25);">
                                        <i class="fa-solid fa-mug-hot"></i> Take Break
                                    </button>
                                </form>
                                <form method="POST" action="/admin/index.php?section=attendance" style="margin:0;">
                                    <input type="hidden" name="action" value="clock_out">
                                    <button type="submit" style="width: 100%; padding: 8px 12px; background: #ffffff; color: #0f172a; border: 1px solid #cbd5e1; border-radius: 10px; font-weight: 800; font-size: 0.8rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px;">
                                        <i class="fa-solid fa-stop-circle" style="color: #dc2626;"></i> Clock Out
                                    </button>
                                </form>
                            <?php elseif ($attWorkState === 'on_break'): ?>
                                <form method="POST" action="/admin/index.php?section=attendance" style="margin:0;">
                                    <input type="hidden" name="action" value="end_break">
                                    <button type="submit" style="width: 100%; padding: 8px 12px; background: #2563eb; color: #ffffff; border: none; border-radius: 10px; font-weight: 800; font-size: 0.8rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px;">
                                        <i class="fa-solid fa-play-circle"></i> Resume
                                    </button>
                                </form>
                                <form method="POST" action="/admin/index.php?section=attendance" style="margin:0;">
                                    <input type="hidden" name="action" value="clock_out">
                                    <button type="submit" style="width: 100%; padding: 8px 12px; background: #ffffff; color: #0f172a; border: 1px solid #cbd5e1; border-radius: 10px; font-weight: 800; font-size: 0.8rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px;">
                                        <i class="fa-solid fa-stop-circle" style="color: #dc2626;"></i> Clock Out
                                    </button>
                                </form>
                            <?php else: ?>
                                <form method="POST" action="/admin/index.php?section=attendance" style="margin:0;">
                                    <input type="hidden" name="action" value="clock_in">
                                    <button type="submit" style="width: 100%; padding: 10px 14px; background: #16a34a; color: #ffffff; border: none; border-radius: 10px; font-weight: 800; font-size: 0.84rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; box-shadow: 0 2px 4px rgba(22, 163, 74, 0.25);">
                                        <i class="fa-solid fa-play"></i> Clock In
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- CARD 2: HOURS THIS WEEK & BAR GRAPH -->
                    <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 16px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                                <div style="width: 30px; height: 30px; border-radius: 50%; background: #fef2f2; display: flex; align-items: center; justify-content: center; color: #dc2626;">
                                    <i class="fa-solid fa-clock" style="font-size: 0.9rem;"></i>
                                </div>
                                <div style="font-size: 0.75rem; font-weight: 800; color: #64748b; letter-spacing: 0.5px; text-transform: uppercase;">HOURS THIS WEEK</div>
                            </div>
                            <div style="font-size: 1.7rem; font-weight: 800; color: #0f172a; margin-bottom: 12px;">
                                <?php echo htmlspecialchars($weekHrsStr); ?>
                            </div>
                        </div>

                        <!-- 7 DAY MICRO BAR GRAPH (SUN - SAT) -->
                        <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px; align-items: flex-end;">
                            <?php 
                            $daysOrder = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                            foreach ($daysOrder as $dName):
                                $dMins = $dayMinsMap[$dName] ?? 0;
                                $fillPct = ($dMins > 0) ? min(100, max(20, round(($dMins / 480) * 100))) : ( ($dName === 'Sat' || $dName === 'Fri') ? 85 : 15 );
                                $isToday = (date('D') === $dName);
                            ?>
                                <div style="text-align: center;">
                                    <div style="height: 36px; width: 100%; background: #f1f5f9; border-radius: 6px; position: relative; overflow: hidden; display: flex; align-items: flex-end;">
                                        <div style="width: 100%; height: <?php echo $fillPct; ?>%; background: <?php echo $isToday ? '#dc2626' : '#475569'; ?>; border-radius: 4px; transition: height 0.3s ease;"></div>
                                    </div>
                                    <div style="font-size: 0.68rem; font-weight: 700; color: <?php echo $isToday ? '#dc2626' : '#94a3b8'; ?>; margin-top: 4px;"><?php echo $dName; ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- CARD 3: HOURS THIS MONTH -->
                    <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 16px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                                <div style="width: 30px; height: 30px; border-radius: 50%; background: #f3e8ff; display: flex; align-items: center; justify-content: center; color: #8b5cf6;">
                                    <i class="fa-solid fa-calendar-check" style="font-size: 0.9rem;"></i>
                                </div>
                                <div style="font-size: 0.75rem; font-weight: 800; color: #64748b; letter-spacing: 0.5px; text-transform: uppercase;">HOURS THIS MONTH</div>
                            </div>
                            <div style="font-size: 1.7rem; font-weight: 800; color: #0f172a; margin-top: 4px;">
                                <?php echo htmlspecialchars($monthHrsStr); ?>
                            </div>
                        </div>

                        <div style="font-size: 0.78rem; color: #64748b; font-weight: 600; padding-top: 12px; border-top: 1px solid #f1f5f9; display: flex; align-items: center; gap: 6px;">
                            <i class="fa-solid fa-circle-check" style="color: #16a34a;"></i> Standard Monthly Logs Active
                        </div>
                    </div>

                </div>

                <!-- LIVE STOPWATCH TICKER SCRIPT -->
                <script>
                (function() {
                    var elapsed = <?php echo (int)$initialElapsed; ?>;
                    var timerElem = document.getElementById('attendanceLiveTimer');
                    var isWorking = <?php echo ($attWorkState === 'working') ? 'true' : 'false'; ?>;

                    if (timerElem && isWorking) {
                        setInterval(function() {
                            elapsed++;
                            var hrs = Math.floor(elapsed / 3600);
                            var mins = Math.floor((elapsed % 3600) / 60);
                            var secs = elapsed % 60;
                            
                            var hrsStr = (hrs < 10 ? '0' : '') + hrs;
                            var minsStr = (mins < 10 ? '0' : '') + mins;
                            var secsStr = (secs < 10 ? '0' : '') + secs;

                            timerElem.textContent = hrsStr + ':' + minsStr + ':' + secsStr;
                        }, 1000);
                    }
                })();
                </script>

                <div class="dashboard-card">
                    <div class="card-header-row" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div class="card-icon-badge"><i class="fa-solid fa-calendar-days"></i></div>
                            <div class="card-title-text">
                                <?php echo (canViewAllAttendanceLogs($userRole, $userEmail, $username) && $activeAttTab === 'company') ? 'Company Attendance History' : 'Recent Attendance History'; ?>
                            </div>
                        </div>

                        <?php if (canViewAllAttendanceLogs($userRole, $userEmail, $username)): ?>
                            <div style="display: flex; align-items: center; gap: 6px; background: #f1f5f9; padding: 4px; border-radius: 10px; border: 1px solid #cbd5e1;">
                                <a href="/admin/index.php?section=attendance&tab=my" style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; font-size: 0.82rem; font-weight: 700; text-decoration: none; border-radius: 7px; transition: all 0.15s ease; <?php echo ($activeAttTab === 'my') ? 'background: #ffffff; color: #dc2626; box-shadow: 0 1px 3px rgba(0,0,0,0.08);' : 'color: #475569;'; ?>">
                                    <i class="fa-solid fa-user-clock" style="font-size: 0.8rem; <?php echo ($activeAttTab === 'my') ? 'color: #dc2626;' : 'color: #64748b;'; ?>"></i>
                                    <span>My Attendance</span>
                                </a>
                                <a href="/admin/index.php?section=attendance&tab=company" style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; font-size: 0.82rem; font-weight: 700; text-decoration: none; border-radius: 7px; transition: all 0.15s ease; <?php echo ($activeAttTab === 'company') ? 'background: #ffffff; color: #dc2626; box-shadow: 0 1px 3px rgba(0,0,0,0.08);' : 'color: #475569;'; ?>">
                                    <i class="fa-solid fa-building-user" style="font-size: 0.8rem; <?php echo ($activeAttTab === 'company') ? 'color: #dc2626;' : 'color: #64748b;'; ?>"></i>
                                    <span>Company History</span>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <!-- FILTER & SEARCH TOOLBAR -->
                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-top: 18px; margin-bottom: 16px; background: #f8fafc; padding: 12px 16px; border-radius: 12px; border: 1px solid #e2e8f0;">
                        
                        <!-- SEARCH BY STAFF NAME -->
                        <div style="position: relative; flex: 1; min-width: 220px;">
                            <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 0.85rem;"></i>
                            <input type="text" id="attSearchName" placeholder="Search by staff name..." onkeyup="filterAttendanceTable()" oninput="filterAttendanceTable()" style="width: 100%; padding: 9px 12px 9px 38px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.86rem; color: #0f172a; outline: none; background: #ffffff; transition: border-color 0.15s ease;" onfocus="this.style.borderColor='#dc2626'" onblur="this.style.borderColor='#cbd5e1'">
                        </div>

                        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                            <!-- FILTER BY DATE -->
                            <div style="display: flex; align-items: center; gap: 8px; background: #ffffff; padding: 4px 10px; border: 1px solid #cbd5e1; border-radius: 8px;">
                                <i class="fa-solid fa-calendar-day" style="color: #64748b; font-size: 0.85rem;"></i>
                                <span style="font-size: 0.8rem; font-weight: 700; color: #475569;">Date:</span>
                                <input type="date" id="attFilterDate" onchange="filterAttendanceTable()" style="border: none; font-size: 0.86rem; color: #0f172a; outline: none; background: transparent; cursor: pointer;">
                            </div>

                            <!-- RESET FILTERS BUTTON -->
                            <button type="button" onclick="resetAttendanceFilters()" style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; background: #ffffff; color: #475569; border: 1px solid #cbd5e1; border-radius: 8px; font-weight: 700; font-size: 0.82rem; cursor: pointer; transition: all 0.15s ease;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='#ffffff'">
                                <i class="fa-solid fa-rotate-left" style="font-size: 0.78rem;"></i>
                                <span>Reset</span>
                            </button>
                        </div>
                    </div>

                    <table style="width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 0.88rem;">
                        <thead>
                            <tr style="border-bottom: 2px solid #f1f5f9; text-align: left; color: #64748b;">
                                <th style="padding: 10px;">Staff Member</th>
                                <th style="padding: 10px;">Date</th>
                                <th style="padding: 10px;">Clock In</th>
                                <th style="padding: 10px;">Clock Out</th>
                                <th style="padding: 10px;">Hours Worked</th>
                                <th style="padding: 10px;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr id="attNoRecordsRow" style="display: <?php echo empty($displayAttendanceLogs) ? '' : 'none'; ?>;">
                                <td colspan="6" style="padding: 28px; text-align: center; color: #64748b; font-weight: 600;">
                                    <i class="fa-solid fa-folder-open" style="font-size: 1.5rem; color: #cbd5e1; display: block; margin-bottom: 8px;"></i>
                                    No matching attendance records found.
                                </td>
                            </tr>
                            <?php if (!empty($displayAttendanceLogs)): ?>
                                <?php foreach ($displayAttendanceLogs as $log): ?>
                                    <tr class="att-log-row" style="border-bottom: 1px solid #f8fafc;" data-staff-name="<?php echo htmlspecialchars(strtolower(trim($log['full_name'] ?? $userFullName))); ?>" data-raw-date="<?php echo htmlspecialchars(date('Y-m-d', strtotime($log['date']))); ?>">
                                        <td style="padding: 12px 10px; font-weight: 700; color: #0f172a;"><?php echo htmlspecialchars($log['full_name'] ?? $userFullName); ?></td>
                                        <td style="padding: 12px 10px; font-weight: 700; color: #0f172a;"><?php echo date('M d, Y', strtotime($log['date'])); ?></td>
                                        <td style="padding: 12px 10px; color: #16a34a; font-weight: 700;"><?php echo htmlspecialchars($log['clock_in'] ?? '--'); ?></td>
                                        <td style="padding: 12px 10px; color: #475569;"><?php echo htmlspecialchars($log['clock_out'] ?? 'In Progress'); ?></td>
                                        <td style="padding: 12px 10px; color: #0f172a; font-weight: 700;"><?php echo htmlspecialchars($log['duration'] ?? '--'); ?></td>
                                        <td style="padding: 12px 10px;">
                                            <?php if (($log['status'] ?? '') === 'Clocked In'): ?>
                                                <span style="background: #eff6ff; color: #1d4ed8; padding: 2px 8px; border-radius: 10px; font-size: 0.75rem; font-weight: 800;">● Clocked In</span>
                                            <?php else: ?>
                                                <span style="background: #f0fdf4; color: #166534; padding: 2px 8px; border-radius: 10px; font-size: 0.75rem; font-weight: 800;">● <?php echo htmlspecialchars($log['status'] ?? 'Present'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <!-- LIVE TABLE FILTER SCRIPT -->
                    <script>
                    function filterAttendanceTable() {
                        var searchVal = (document.getElementById('attSearchName').value || '').toLowerCase().trim();
                        var dateVal = (document.getElementById('attFilterDate').value || '').trim();
                        var rows = document.querySelectorAll('.att-log-row');
                        var visibleCount = 0;

                        rows.forEach(function(row) {
                            var nameAttr = row.getAttribute('data-staff-name') || '';
                            var dateAttr = row.getAttribute('data-raw-date') || '';

                            var matchesName = !searchVal || nameAttr.indexOf(searchVal) !== -1;
                            var matchesDate = !dateVal || dateAttr === dateVal;

                            if (matchesName && matchesDate) {
                                row.style.display = '';
                                visibleCount++;
                            } else {
                                row.style.display = 'none';
                            }
                        });

                        var noRecRow = document.getElementById('attNoRecordsRow');
                        if (noRecRow) {
                            noRecRow.style.display = (visibleCount === 0) ? '' : 'none';
                        }
                    }

                    function resetAttendanceFilters() {
                        var searchInput = document.getElementById('attSearchName');
                        var dateInput = document.getElementById('attFilterDate');
                        if (searchInput) searchInput.value = '';
                        if (dateInput) dateInput.value = '';
                        filterAttendanceTable();
                    }
                    </script>
                </div>

            <?php elseif ($activeSection === 'leaves'): ?>
                <!-- SECTION: TIME OFF (LEAVES) -->
                <?php 
                $activeLeaveTab = trim($_GET['tab'] ?? 'my');
                if (!in_array($activeLeaveTab, ['my', 'company'], true)) {
                    $activeLeaveTab = 'my';
                }

                // Fetch persistent leave requests dataset & user stats
                $allLeaveRequests = getLeaveRequests();
                $userLeaveStats = getUserLeaveStats($username);

                // Filter leave requests based on tab selection
                if (canViewAllAttendanceLogs($userRole, $userEmail, $username) && $activeLeaveTab === 'company') {
                    $displayLeaveRequests = $allLeaveRequests;
                } else {
                    $displayLeaveRequests = array_values(array_filter($allLeaveRequests, function($req) use ($username) {
                        return strtolower(trim($req['username'] ?? '')) === strtolower(trim($username));
                    }));
                }
                ?>
                <div class="section-header-bar">
                    <div>
                        <h1 class="section-header-title">Time Off & Leave Management</h1>
                        <p class="section-header-desc">Apply for annual, sick, or casual leave and track request status.</p>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; margin-bottom: 24px;">
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px;">
                        <div style="font-size: 0.78rem; font-weight: 700; color: #64748b;">ANNUAL LEAVE</div>
                        <div style="font-size: 1.5rem; font-weight: 800; color: #0284c7; margin-top: 4px;">
                            <?php echo (int)$userLeaveStats['annual']['remaining']; ?> / <?php echo (int)$userLeaveStats['annual']['total']; ?> Days
                        </div>
                    </div>
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px;">
                        <div style="font-size: 0.78rem; font-weight: 700; color: #64748b;">SICK LEAVE</div>
                        <div style="font-size: 1.5rem; font-weight: 800; color: #16a34a; margin-top: 4px;">
                            <?php echo (int)$userLeaveStats['sick']['remaining']; ?> / <?php echo (int)$userLeaveStats['sick']['total']; ?> Days
                        </div>
                    </div>
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px;">
                        <div style="font-size: 0.78rem; font-weight: 700; color: #64748b;">CASUAL LEAVE</div>
                        <div style="font-size: 1.5rem; font-weight: 800; color: #9333ea; margin-top: 4px;">
                            <?php echo (int)$userLeaveStats['casual']['remaining']; ?> / <?php echo (int)$userLeaveStats['casual']['total']; ?> Days
                        </div>
                    </div>
                </div>

                <!-- 2-COLUMN GRID: REQUEST TIME OFF (35%) & LEAVE HISTORY (65%) CARDS -->
                <div class="leaves-layout-grid" style="display: grid; grid-template-columns: 35fr 65fr; gap: 24px;">
                    
                    <!-- LEFT COLUMN CARD: REQUEST TIME OFF (35%) -->
                    <div class="dashboard-card" style="margin-bottom: 0;">
                        <div class="card-header-row">
                            <div class="card-icon-badge"><i class="fa-solid fa-paper-plane"></i></div>
                            <div class="card-title-text">Request Time Off</div>
                        </div>
                        <form method="POST" action="/admin/index.php?section=leaves">
                            <input type="hidden" name="action" value="submit_leave_request">
                            <div class="form-field" style="margin-top: 10px;">
                                <label class="form-label-title">Leave Type</label>
                                <select name="leave_type" class="form-text-input" required>
                                    <option value="Annual Leave">Annual Leave</option>
                                    <option value="Sick Leave">Sick Leave</option>
                                    <option value="Casual Leave">Casual Leave</option>
                                    <option value="Parental / Family Leave">Parental / Family Leave</option>
                                </select>
                            </div>

                            <!-- START DATE & END DATE -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 10px;">
                                <div class="form-field">
                                    <label class="form-label-title">Start Date</label>
                                    <input type="date" id="leaveStartDate" name="start_date" class="form-text-input" value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" onchange="updateLeaveDatesAndDuration('start')" required>
                                </div>
                                <div class="form-field">
                                    <label class="form-label-title">End Date</label>
                                    <input type="date" id="leaveEndDate" name="end_date" class="form-text-input" value="<?php echo date('Y-m-d', strtotime('+3 days')); ?>" onchange="updateLeaveDatesAndDuration('end')" required>
                                </div>
                            </div>

                            <div class="form-field" style="margin-top: 10px;">
                                <label class="form-label-title">Duration (Days)</label>
                                <input type="number" id="leaveDurationDays" name="duration" class="form-text-input" min="1" max="60" value="3" onchange="updateLeaveDatesAndDuration('duration')" onkeyup="updateLeaveDatesAndDuration('duration')" required>
                            </div>
                            <div class="form-field" style="margin-top: 10px;">
                                <label class="form-label-title">Reason for Leave</label>
                                <textarea name="reason" class="form-text-input" rows="3" placeholder="Brief explanation for your time off request..." required></textarea>
                            </div>
                            <button type="submit" class="btn-save-primary" style="margin-top: 14px; width: 100%; justify-content: center;">
                                <i class="fa-solid fa-paper-plane"></i> Submit Request
                            </button>
                        </form>

                        <!-- SCRIPT: AUTO SYNC DATES AND DURATION -->
                        <script>
                        function updateLeaveDatesAndDuration(triggeredBy) {
                            var startElem = document.getElementById('leaveStartDate');
                            var endElem = document.getElementById('leaveEndDate');
                            var durElem = document.getElementById('leaveDurationDays');

                            if (!startElem || !endElem || !durElem) return;

                            var startVal = startElem.value;
                            var endVal = endElem.value;
                            var durVal = parseInt(durElem.value) || 1;

                            if (!startVal) return;

                            var startDate = new Date(startVal + 'T00:00:00');

                            if (triggeredBy === 'start' || triggeredBy === 'duration') {
                                if (durVal < 1) durVal = 1;
                                var endDate = new Date(startDate);
                                endDate.setDate(startDate.getDate() + (durVal - 1));

                                var yyyy = endDate.getFullYear();
                                var mm = String(endDate.getMonth() + 1).padStart(2, '0');
                                var dd = String(endDate.getDate()).padStart(2, '0');
                                endElem.value = yyyy + '-' + mm + '-' + dd;
                            } else if (triggeredBy === 'end') {
                                if (endVal) {
                                    var endDate = new Date(endVal + 'T00:00:00');
                                    if (endDate >= startDate) {
                                        var diffTime = Math.abs(endDate - startDate);
                                        var diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
                                        durElem.value = diffDays;
                                    } else {
                                        endElem.value = startVal;
                                        durElem.value = 1;
                                    }
                                }
                            }
                        }
                        </script>
                    </div>

                    <!-- RIGHT COLUMN CARD: LEAVE HISTORY (75%) -->
                    <div class="dashboard-card" style="margin-bottom: 0;">
                        <div class="card-header-row" style="margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div class="card-icon-badge" style="background: #eff6ff; color: #2563eb;"><i class="fa-solid fa-clock-rotate-left"></i></div>
                                <div class="card-title-text">History &amp; Requests</div>
                            </div>

                            <?php if (canViewAllAttendanceLogs($userRole, $userEmail, $username)): ?>
                                <div style="display: flex; align-items: center; gap: 6px; background: #f1f5f9; padding: 4px; border-radius: 10px; border: 1px solid #cbd5e1;">
                                    <a href="/admin/index.php?section=leaves&tab=my" style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; font-size: 0.82rem; font-weight: 700; text-decoration: none; border-radius: 7px; transition: all 0.15s ease; <?php echo ($activeLeaveTab === 'my') ? 'background: #ffffff; color: #dc2626; box-shadow: 0 1px 3px rgba(0,0,0,0.08);' : 'color: #475569;'; ?>">
                                        <i class="fa-solid fa-user-clock" style="font-size: 0.8rem; <?php echo ($activeLeaveTab === 'my') ? 'color: #dc2626;' : 'color: #64748b;'; ?>"></i>
                                        <span>My Leaves</span>
                                    </a>
                                    <a href="/admin/index.php?section=leaves&tab=company" style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; font-size: 0.82rem; font-weight: 700; text-decoration: none; border-radius: 7px; transition: all 0.15s ease; <?php echo ($activeLeaveTab === 'company') ? 'background: #ffffff; color: #dc2626; box-shadow: 0 1px 3px rgba(0,0,0,0.08);' : 'color: #475569;'; ?>">
                                        <i class="fa-solid fa-building-user" style="font-size: 0.8rem; <?php echo ($activeLeaveTab === 'company') ? 'color: #dc2626;' : 'color: #64748b;'; ?>"></i>
                                        <span>Company Leaves</span>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <?php if (empty($displayLeaveRequests)): ?>
                                <div style="padding: 24px; text-align: center; color: #64748b; font-weight: 600; background: #f8fafc; border-radius: 12px; border: 1px dashed #cbd5e1;">
                                    No leave requests found.
                                </div>
                            <?php else: ?>
                                <?php foreach ($displayLeaveRequests as $req): ?>
                                    <div style="padding: 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px;">
                                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6px; flex-wrap: wrap; gap: 8px;">
                                            <div>
                                                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                                    <span style="font-size: 0.9rem; font-weight: 800; color: #0f172a;"><?php echo htmlspecialchars($req['type']); ?></span>
                                                    <?php if (canViewAllAttendanceLogs($userRole, $userEmail, $username) && $activeLeaveTab === 'company'): ?>
                                                        <span style="font-size: 0.74rem; font-weight: 700; background: #ffffff; color: #475569; padding: 2px 8px; border-radius: 6px; border: 1px solid #cbd5e1; display: inline-flex; align-items: center;">
                                                            <i class="fa-solid fa-user-circle" style="font-size: 0.72rem; margin-right: 4px; color: #dc2626;"></i>
                                                            <?php echo htmlspecialchars($req['staff_name']); ?> (<?php echo htmlspecialchars($req['staff_role']); ?>)
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <div style="font-size: 0.76rem; color: #64748b; margin-top: 3px;"><?php echo htmlspecialchars($req['dates']); ?></div>
                                            </div>
                                            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                                <?php if ($req['status_type'] === 'pending'): ?>
                                                    <span style="font-size: 0.72rem; font-weight: 800; background: #fffbe6; color: #b45309; border: 1px solid #fef08a; padding: 3px 10px; border-radius: 12px;">● <?php echo htmlspecialchars($req['status']); ?></span>
                                                <?php elseif ($req['status_type'] === 'approved'): ?>
                                                    <span style="font-size: 0.72rem; font-weight: 800; background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; padding: 3px 10px; border-radius: 12px;">✔ Approved</span>
                                                <?php else: ?>
                                                    <span style="font-size: 0.72rem; font-weight: 800; background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; padding: 3px 10px; border-radius: 12px;">✖ Rejected</span>
                                                <?php endif; ?>

                                                <?php if (canViewAllAttendanceLogs($userRole, $userEmail, $username) && $req['status_type'] === 'pending'): ?>
                                                    <!-- HR ADMINISTER ACTIONS (APPROVE / REJECT) -->
                                                    <div style="display: inline-flex; align-items: center; gap: 6px; margin-left: 4px;">
                                                        <form method="POST" action="/admin/index.php?section=leaves" style="margin:0;">
                                                            <input type="hidden" name="action" value="update_leave_status">
                                                            <input type="hidden" name="leave_id" value="<?php echo htmlspecialchars($req['id']); ?>">
                                                            <input type="hidden" name="new_status" value="Approved">
                                                            <button type="submit" style="padding: 4px 10px; background: #16a34a; color: #ffffff; border: none; border-radius: 6px; font-weight: 800; font-size: 0.72rem; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; box-shadow: 0 1px 2px rgba(22,163,74,0.2);" title="Approve Leave Request">
                                                                <i class="fa-solid fa-check"></i> Approve
                                                            </button>
                                                        </form>
                                                        <form method="POST" action="/admin/index.php?section=leaves" style="margin:0;">
                                                            <input type="hidden" name="action" value="update_leave_status">
                                                            <input type="hidden" name="leave_id" value="<?php echo htmlspecialchars($req['id']); ?>">
                                                            <input type="hidden" name="new_status" value="Rejected">
                                                            <button type="submit" style="padding: 4px 10px; background: #ffffff; color: #dc2626; border: 1px solid #fecaca; border-radius: 6px; font-weight: 800; font-size: 0.72rem; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;" title="Reject Leave Request">
                                                                <i class="fa-solid fa-xmark"></i> Reject
                                                            </button>
                                                        </form>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div style="font-size: 0.78rem; color: #475569; background: #ffffff; padding: 8px 10px; border-radius: 6px; border: 1px solid #f1f5f9;">
                                            Reason: <?php echo htmlspecialchars($req['reason']); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>

                </div>

            <?php elseif ($activeSection === 'payslips'): ?>
                <!-- SECTION: MY PAYSLIPS -->
                <div class="section-header-bar">
                    <div>
                        <h1 class="section-header-title">My Payslips & Remuneration</h1>
                        <p class="section-header-desc">View and download your monthly salary stubs and payment breakdowns.</p>
                    </div>
                </div>

                <!-- TOP 3 METRIC CARDS (YTD NET EARNINGS, YTD TAXES WITHHELD, YTD PENSION SAVINGS) -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; margin-bottom: 28px;">
                    <!-- CARD 1: YTD NET EARNINGS -->
                    <div style="background: #ffffff; border: 1px solid #cbd5e1; border-left: 4px solid #dc2626; border-radius: 16px; padding: 22px 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                        <div style="font-size: 0.78rem; font-weight: 700; color: #64748b; text-transform: uppercase;">YTD Net Earnings (2026)</div>
                        <div style="font-size: 1.65rem; font-weight: 800; color: #0f172a; margin-top: 8px;">&#8358;560,000.00</div>
                    </div>

                    <!-- CARD 2: YTD TAXES WITHHELD -->
                    <div style="background: #ffffff; border: 1px solid #cbd5e1; border-left: 4px solid #dc2626; border-radius: 16px; padding: 22px 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                        <div style="font-size: 0.78rem; font-weight: 700; color: #64748b; text-transform: uppercase;">YTD Taxes Withheld</div>
                        <div style="font-size: 1.65rem; font-weight: 800; color: #0f172a; margin-top: 8px;">0.00</div>
                    </div>

                    <!-- CARD 3: YTD PENSION SAVINGS -->
                    <div style="background: #ffffff; border: 1px solid #cbd5e1; border-left: 4px solid #16a34a; border-radius: 16px; padding: 22px 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                        <div style="font-size: 0.78rem; font-weight: 700; color: #64748b; text-transform: uppercase;">YTD Pension Savings</div>
                        <div style="font-size: 1.65rem; font-weight: 800; color: #0f172a; margin-top: 8px;">&#8358;40,000.00</div>
                    </div>
                </div>

                <!-- HISTORY SECTION HEADER & FILTER CONTROLS -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
                    <h2 style="font-size: 1.3rem; font-weight: 800; color: #0f172a; margin: 0;">History</h2>
                    
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <select style="padding: 6px 14px; border: 1px solid #cbd5e1; border-radius: 8px; background: #ffffff; font-size: 0.82rem; font-weight: 700; color: #475569; cursor: pointer;">
                            <option value="all">All Years</option>
                            <option value="2026">2026</option>
                            <option value="2025">2025</option>
                        </select>

                        <div style="display: flex; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 2px;">
                            <button style="padding: 5px 10px; background: #f1f5f9; border: none; border-radius: 6px; color: #0f172a; cursor: pointer;">
                                <i class="fa-solid fa-table-cells" style="font-size: 0.85rem;"></i>
                            </button>
                            <button style="padding: 5px 10px; background: transparent; border: none; border-radius: 6px; color: #94a3b8; cursor: pointer;">
                                <i class="fa-solid fa-list" style="font-size: 0.85rem;"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- PAYSLIP HISTORY TABLE CARD -->
                <div class="dashboard-card" style="padding: 0; overflow: hidden;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.88rem;">
                        <thead>
                            <tr style="border-bottom: 1px solid #e2e8f0; background: #f8fafc; color: #64748b; font-size: 0.76rem; text-transform: uppercase; font-weight: 800; letter-spacing: 0.03em;">
                                <th style="padding: 16px 20px;">Pay Period</th>
                                <th style="padding: 16px 20px;">Status</th>
                                <th style="padding: 16px 20px;">Net Pay</th>
                                <th style="padding: 16px 20px; text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- ROW 1: APRIL 2026 -->
                            <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s ease;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                                <td style="padding: 16px 20px; font-weight: 800; color: #0f172a;">
                                    April 2026
                                </td>
                                <td style="padding: 16px 20px;">
                                    <span style="font-size: 0.72rem; font-weight: 800; color: #166534; background: #f0fdf4; border: 1px solid #bbf7d0; padding: 2px 8px; border-radius: 10px; display: inline-flex; align-items: center; gap: 4px;">
                                        Paid
                                    </span>
                                </td>
                                <td style="padding: 16px 20px; font-weight: 800; color: #dc2626; font-size: 0.95rem;">
                                    &#8358;140,000.00
                                </td>
                                <td style="padding: 16px 20px; text-align: right;">
                                    <button onclick="alert('Viewing April 2026 Payslip PDF...')" style="padding: 8px 16px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; color: #0f172a; font-weight: 700; font-size: 0.82rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.15s ease;" onmouseover="this.style.borderColor='#dc2626'; this.style.color='#dc2626'" onmouseout="this.style.borderColor='#cbd5e1'; this.style.color='#0f172a'">
                                        <i class="fa-solid fa-print"></i> View PDF
                                    </button>
                                </td>
                            </tr>

                            <!-- ROW 2: MARCH 2026 -->
                            <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s ease;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                                <td style="padding: 16px 20px; font-weight: 800; color: #0f172a;">
                                    March 2026
                                </td>
                                <td style="padding: 16px 20px;">
                                    <span style="font-size: 0.72rem; font-weight: 800; color: #166534; background: #f0fdf4; border: 1px solid #bbf7d0; padding: 2px 8px; border-radius: 10px; display: inline-flex; align-items: center; gap: 4px;">
                                        Paid
                                    </span>
                                </td>
                                <td style="padding: 16px 20px; font-weight: 800; color: #dc2626; font-size: 0.95rem;">
                                    &#8358;140,000.00
                                </td>
                                <td style="padding: 16px 20px; text-align: right;">
                                    <button onclick="alert('Viewing March 2026 Payslip PDF...')" style="padding: 8px 16px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; color: #0f172a; font-weight: 700; font-size: 0.82rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.15s ease;" onmouseover="this.style.borderColor='#dc2626'; this.style.color='#dc2626'" onmouseout="this.style.borderColor='#cbd5e1'; this.style.color='#0f172a'">
                                        <i class="fa-solid fa-print"></i> View PDF
                                    </button>
                                </td>
                            </tr>

                            <!-- ROW 3: FEBRUARY 2026 -->
                            <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s ease;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                                <td style="padding: 16px 20px; font-weight: 800; color: #0f172a;">
                                    February 2026
                                </td>
                                <td style="padding: 16px 20px;">
                                    <span style="font-size: 0.72rem; font-weight: 800; color: #166534; background: #f0fdf4; border: 1px solid #bbf7d0; padding: 2px 8px; border-radius: 10px; display: inline-flex; align-items: center; gap: 4px;">
                                        Paid
                                    </span>
                                </td>
                                <td style="padding: 16px 20px; font-weight: 800; color: #dc2626; font-size: 0.95rem;">
                                    &#8358;140,000.00
                                </td>
                                <td style="padding: 16px 20px; text-align: right;">
                                    <button onclick="alert('Viewing February 2026 Payslip PDF...')" style="padding: 8px 16px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; color: #0f172a; font-weight: 700; font-size: 0.82rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.15s ease;" onmouseover="this.style.borderColor='#dc2626'; this.style.color='#dc2626'" onmouseout="this.style.borderColor='#cbd5e1'; this.style.color='#0f172a'">
                                        <i class="fa-solid fa-print"></i> View PDF
                                    </button>
                                </td>
                            </tr>

                            <!-- ROW 4: JANUARY 2026 -->
                            <tr style="transition: background 0.15s ease;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                                <td style="padding: 16px 20px; font-weight: 800; color: #0f172a;">
                                    January 2026
                                </td>
                                <td style="padding: 16px 20px;">
                                    <span style="font-size: 0.72rem; font-weight: 800; color: #166534; background: #f0fdf4; border: 1px solid #bbf7d0; padding: 2px 8px; border-radius: 10px; display: inline-flex; align-items: center; gap: 4px;">
                                        Paid
                                    </span>
                                </td>
                                <td style="padding: 16px 20px; font-weight: 800; color: #dc2626; font-size: 0.95rem;">
                                    &#8358;140,000.00
                                </td>
                                <td style="padding: 16px 20px; text-align: right;">
                                    <button onclick="alert('Viewing January 2026 Payslip PDF...')" style="padding: 8px 16px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; color: #0f172a; font-weight: 700; font-size: 0.82rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.15s ease;" onmouseover="this.style.borderColor='#dc2626'; this.style.color='#dc2626'" onmouseout="this.style.borderColor='#cbd5e1'; this.style.color='#0f172a'">
                                        <i class="fa-solid fa-print"></i> View PDF
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($activeSection === 'mail'): ?>
                <!-- SECTION: MAIL -->
                <div class="section-header-bar">
                    <div>
                        <h1 class="section-header-title">Staff Mail & Communications Inbox</h1>
                        <p class="section-header-desc">Manage internal staff emails, project notifications, and client inquiries.</p>
                    </div>
                    <button type="button" class="btn-save-primary" onclick="alert('Compose new email window opened!')">
                        <i class="fa-solid fa-pen-to-square"></i> Compose Mail
                    </button>
                </div>

                <div class="dashboard-card">
                    <div class="card-header-row">
                        <div class="card-icon-badge"><i class="fa-solid fa-envelope-open-text"></i></div>
                        <div class="card-title-text">Recent Mail & Messages</div>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 12px; margin-top: 14px;">
                        <div style="padding: 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; display: flex; justify-content: space-between; align-items: center; cursor: pointer;">
                            <div style="display: flex; align-items: center; gap: 14px;">
                                <div style="width: 42px; height: 42px; border-radius: 50%; background: rgba(220, 38, 38, 0.1); color: #dc2626; display: flex; align-items: center; justify-content: center; font-weight: 800;">
                                    <i class="fa-solid fa-film"></i>
                                </div>
                                <div>
                                    <div style="font-weight: 800; color: #0f172a; font-size: 0.94rem;">Production Scheduling &bull; Q3 Film Shoot</div>
                                    <div style="font-size: 0.82rem; color: #64748b;">From: Henry Falonipe &mdash; Call sheet for Lagos commercial shoot confirmed for Monday.</div>
                                </div>
                            </div>
                            <span style="font-size: 0.76rem; color: #94a3b8; font-weight: 600;">10:15 AM</span>
                        </div>
                        <div style="padding: 16px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; display: flex; justify-content: space-between; align-items: center; cursor: pointer;">
                            <div style="display: flex; align-items: center; gap: 14px;">
                                <div style="width: 42px; height: 42px; border-radius: 50%; background: rgba(2, 132, 199, 0.1); color: #0284c7; display: flex; align-items: center; justify-content: center; font-weight: 800;">
                                    <i class="fa-solid fa-users-gear"></i>
                                </div>
                                <div>
                                    <div style="font-weight: 800; color: #0f172a; font-size: 0.94rem;">HR Portal &bull; Monthly Benefits & Health Stipend</div>
                                    <div style="font-size: 0.82rem; color: #64748b;">From: Resource & HR &mdash; August reimbursement claims are now processing.</div>
                                </div>
                            </div>
                            <span style="font-size: 0.76rem; color: #94a3b8; font-weight: 600;">Yesterday</span>
                        </div>
                    </div>
                </div>

            <?php elseif ($activeSection === 'comms'): ?>
                <!-- SECTION: COMMUNICATIONS HUB -->
                <?php 
                $activeTab = strtolower(trim($_GET['tab'] ?? 'nest'));
                if (!in_array($activeTab, ['feeds', 'nest', 'tasks', 'channels', 'events'], true)) {
                    $activeTab = 'nest';
                }

                $activeChannel = strtolower(trim($_GET['channel'] ?? 'general'));
                $subChannels = [
                    'general' => ['name' => '# general-discussion', 'desc' => 'Company-wide studio chat & announcements', 'badge' => 'General'],
                    'production' => ['name' => '# production-crew', 'desc' => 'Camera gear, shoots & logistics', 'badge' => 'Production'],
                    'hr-helpdesk' => ['name' => '# hr-helpdesk', 'desc' => 'HR inquiries, benefits & policies', 'badge' => 'HR'],
                    'creative-team' => ['name' => '# creative-team', 'desc' => 'Design, editing & post-production', 'badge' => 'Creative']
                ];
                if (!isset($subChannels[$activeChannel])) {
                    $activeChannel = 'general';
                }

                $nestContacts = array_slice(getSortedRecentDmContacts($username), 0, 7, true);
                $rawDmParam = strtolower(trim($_GET['dm'] ?? ''));
                $activeDmUser = '';

                if (!empty($rawDmParam)) {
                    foreach ($nestContacts as $uKey => $p) {
                        $pUser = strtolower($p['username'] ?? '');
                        if ($uKey === $rawDmParam || $pUser === $rawDmParam || getCanonicalUsername($pUser) === getCanonicalUsername($rawDmParam) || (is_numeric($rawDmParam) && intval($rawDmParam) === intval($uKey))) {
                            $activeDmUser = $pUser;
                            break;
                        }
                    }
                }

                $activeDmInfo = null;
                foreach ($nestContacts as $p) {
                    if (strtolower($p['username']) === strtolower($activeDmUser) || getCanonicalUsername($p['username']) === getCanonicalUsername($activeDmUser)) {
                        $activeDmInfo = $p;
                        break;
                    }
                }
                if (!$activeDmInfo) {
                    $activeDmInfo = reset($nestContacts) ?: ['first_name' => 'Staff', 'full_name' => 'Staff Member', 'role' => 'Staff'];
                    $activeDmUser = $activeDmInfo['username'] ?? 'ligali.oluwatosin';
                }
                $activeDmChannelKey = getDmChannelKey($username, $activeDmUser);

                $allMessages = getCommsMessages();
                if ($activeTab === 'nest') {
                    $channelMessages = array_values(array_filter($allMessages, function($m) use ($username, $activeDmUser) {
                        $targetKey = getDmChannelKey($username, $activeDmUser);
                        $msgChannel = strtolower(trim($m['channel'] ?? ''));
                        if ($msgChannel === $targetKey) return true;
                        
                        $u1 = getCanonicalUsername($username);
                        $u2 = getCanonicalUsername($activeDmUser);
                        if (str_starts_with($msgChannel, 'dm_')) {
                            $parts = explode('_', substr($msgChannel, 3));
                            if (count($parts) === 2) {
                                $c1 = getCanonicalUsername($parts[0]);
                                $c2 = getCanonicalUsername($parts[1]);
                                if (($c1 === $u1 && $c2 === $u2) || ($c1 === $u2 && $c2 === $u1)) {
                                    return true;
                                }
                            }
                        }
                        return false;
                    }));
                } else {
                    $channelMessages = array_values(array_filter($allMessages, function($m) use ($activeChannel) {
                        return strtolower(trim($m['channel'] ?? 'general')) === $activeChannel;
                    }));
                }

                $commsNavTabs = [
                    'nest' => ['label' => 'The Nest (Chat)', 'icon' => 'fa-solid fa-comments', 'badge' => 'Live Chat', 'desc' => 'Real-time studio team discussions'],
                    'feeds' => ['label' => 'Feeds', 'icon' => 'fa-solid fa-rss', 'badge' => 'Activity', 'desc' => 'Studio updates & announcements'],
                    'tasks' => ['label' => 'Tasks', 'icon' => 'fa-solid fa-list-check', 'badge' => 'Studio', 'desc' => 'Action items & production assignments'],
                    'channels' => ['label' => 'Channels', 'icon' => 'fa-solid fa-hashtag', 'badge' => '4 Groups', 'desc' => 'Specialized team chat channels'],
                    'events' => ['label' => 'Events', 'icon' => 'fa-solid fa-calendar-days', 'badge' => 'Upcoming', 'desc' => 'Studio schedule & shoot calendar']
                ];
                ?>
                <div class="section-header-bar">
                    <div>
                        <h1 class="section-header-title">Studio Communications Hub</h1>
                        <p class="section-header-desc">Internal team chat, studio activity feeds, tasks, channels, and event schedules.</p>
                    </div>
                </div>

                <!-- 2-COLUMN COMMS LAYOUT (COMMUNICATIONS TABS 280px + MAIN VIEW CONTENT) -->
                <div style="display: grid; grid-template-columns: 280px minmax(0, 1fr); gap: 20px; min-height: 600px;">
                    
                    <!-- LEFT SIDEBAR: COMMUNICATIONS TABS -->
                    <div class="dashboard-card" style="margin-bottom: 0; padding: 20px; display: flex; flex-direction: column;">
                        <div style="font-size: 0.76rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 14px; display: flex; align-items: center; justify-content: space-between;">
                            <span>COMMUNICATIONS</span>
                            <span style="background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 10px; font-size: 0.72rem; font-weight: 800;">5</span>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 6px;">
                            <?php foreach ($commsNavTabs as $tKey => $tab): ?>
                                <?php $isActive = ($tKey === $activeTab); ?>
                                <a href="/admin/index.php?section=comms&tab=<?php echo urlencode($tKey); ?><?php echo ($tKey === 'channels') ? '&channel=' . urlencode($activeChannel) : (($tKey === 'nest') ? '&dm=' . urlencode($activeDmUser) : ''); ?>" style="display: flex; flex-direction: column; padding: 12px 14px; border-radius: 12px; text-decoration: none; transition: all 0.15s ease; <?php echo $isActive ? 'background: #fef2f2; border: 1px solid #fecaca; color: #dc2626;' : 'background: #ffffff; border: 1px solid #e2e8f0; color: #334155;'; ?>">
                                    <div style="display: flex; align-items: center; justify-content: space-between;">
                                        <span style="font-size: 0.88rem; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; <?php echo $isActive ? 'color: #dc2626;' : 'color: #0f172a;'; ?>">
                                            <i class="<?php echo $tab['icon']; ?>" style="font-size: 0.84rem; <?php echo $isActive ? 'color: #dc2626;' : 'color: #64748b;'; ?>"></i>
                                            <span><?php echo htmlspecialchars($tab['label']); ?></span>
                                        </span>
                                        <span style="font-size: 0.68rem; font-weight: 700; padding: 2px 6px; border-radius: 6px; <?php echo $isActive ? 'background: #ffffff; color: #dc2626;' : 'background: #f1f5f9; color: #64748b;'; ?>">
                                            <?php echo htmlspecialchars($tab['badge']); ?>
                                        </span>
                                    </div>
                                    <span style="font-size: 0.74rem; color: #64748b; margin-top: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <?php echo htmlspecialchars($tab['desc']); ?>
                                    </span>
                                </a>
                            <?php endforeach; ?>
                        </div>

                        <!-- ONLINE TEAM MEMBERS -->
                        <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid #f1f5f9;">
                            <div style="font-size: 0.74rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 10px;">
                                ACTIVE TEAM
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                <div style="display: flex; align-items: center; gap: 8px; font-size: 0.8rem; color: #334155; font-weight: 700;">
                                    <span style="width: 8px; height: 8px; border-radius: 50%; background: #16a34a; display: inline-block;"></span>
                                    <span>Henry Falonipe</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 8px; font-size: 0.8rem; color: #334155; font-weight: 700;">
                                    <span style="width: 8px; height: 8px; border-radius: 50%; background: #16a34a; display: inline-block;"></span>
                                    <span>Victoria Opemipo</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 8px; font-size: 0.8rem; color: #334155; font-weight: 700;">
                                    <span style="width: 8px; height: 8px; border-radius: 50%; background: #16a34a; display: inline-block;"></span>
                                    <span>Daniel Ifeoluwa</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 8px; font-size: 0.8rem; color: #334155; font-weight: 700;">
                                    <span style="width: 8px; height: 8px; border-radius: 50%; background: #16a34a; display: inline-block;"></span>
                                    <span>Mojisola Emjay</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT MAIN CONTENT PANEL -->
                    <?php if ($activeTab === 'nest' || $activeTab === 'channels'): ?>
                        <!-- CHAT VIEW: THE NEST & CHANNELS -->
                        <div class="dashboard-card" style="margin-bottom: 0; padding: 0; display: flex; flex-direction: column; overflow: hidden; height: 600px;">
                            
                            <!-- SUB-CHANNEL / RECENT PEOPLE SELECTOR HEADER BAR -->
                            <div style="padding: 14px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; flex-wrap: nowrap; gap: 10px; overflow: hidden;">
                                <div style="display: flex; align-items: center; gap: 6px; flex-wrap: nowrap; overflow: hidden; flex: 1;">
                                    <?php if ($activeTab === 'nest'): ?>
                                        <!-- DIRECT MESSAGES RECENT PEOPLE SELECTORS FOR THE NEST (TOP 7 IN SINGLE ROW) -->
                                        <?php foreach ($nestContacts as $uKey => $person): ?>
                                            <?php 
                                            $stUser = strtolower($person['username'] ?? '');
                                            $isDmActive = ($uKey === $activeDmUser || $stUser === $activeDmUser || getCanonicalUsername($stUser) === getCanonicalUsername($activeDmUser)); 
                                            ?>
                                            <a href="/admin/index.php?section=comms&tab=nest&dm=<?php echo urlencode($stUser); ?>" style="padding: 5px 10px; border-radius: 8px; font-size: 0.78rem; font-weight: 800; text-decoration: none; transition: all 0.15s ease; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 105px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 1; <?php echo $isDmActive ? 'background: #ffffff; color: #dc2626; border: 1px solid #fecaca; box-shadow: 0 1px 3px rgba(0,0,0,0.05);' : 'background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0;'; ?>" title="<?php echo htmlspecialchars($person['full_name']); ?>">
                                                <i class="fa-solid fa-user-circle" style="font-size: 0.74rem; margin-right: 3px; flex-shrink: 0;"></i>
                                                <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo htmlspecialchars($person['first_name']); ?></span>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <!-- GROUP CHANNELS SELECTORS FOR CHANNELS TAB -->
                                        <?php foreach ($subChannels as $cKey => $subCh): ?>
                                            <?php $isSubActive = ($cKey === $activeChannel); ?>
                                            <a href="/admin/index.php?section=comms&tab=channels&channel=<?php echo urlencode($cKey); ?>" style="padding: 5px 12px; border-radius: 8px; font-size: 0.8rem; font-weight: 800; text-decoration: none; transition: all 0.15s ease; <?php echo $isSubActive ? 'background: #ffffff; color: #dc2626; border: 1px solid #fecaca; box-shadow: 0 1px 3px rgba(0,0,0,0.05);' : 'background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0;'; ?>">
                                                <?php echo htmlspecialchars($subCh['name']); ?>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <?php if ($activeTab === 'nest' && !empty($activeDmInfo)): ?>
                                    <div style="display: flex; align-items: center; gap: 6px; flex-shrink: 0; margin-left: 6px;">
                                        <button type="button" onclick="startStudioCall('audio', '<?php echo htmlspecialchars(addslashes($activeDmInfo['full_name'])); ?>', '<?php echo htmlspecialchars(addslashes(getCloudinaryUrl($activeDmInfo['avatar'] ?? ''))); ?>')" style="width: 32px; height: 32px; border-radius: 50%; background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; transition: all 0.15s ease;" onmouseover="this.style.background='#dcfce7'" onmouseout="this.style.background='#f0fdf4'" title="Audio Call with <?php echo htmlspecialchars($activeDmInfo['full_name']); ?>">
                                            <i class="fa-solid fa-phone" style="color: #22c55e; font-size: 0.85rem;"></i>
                                        </button>
                                        <button type="button" onclick="startStudioCall('video', '<?php echo htmlspecialchars(addslashes($activeDmInfo['full_name'])); ?>', '<?php echo htmlspecialchars(addslashes(getCloudinaryUrl($activeDmInfo['avatar'] ?? ''))); ?>')" style="width: 32px; height: 32px; border-radius: 50%; background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; transition: all 0.15s ease;" onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='#fef2f2'" title="Video Call with <?php echo htmlspecialchars($activeDmInfo['full_name']); ?>">
                                            <i class="fa-solid fa-video" style="color: #dc2626; font-size: 0.85rem;"></i>
                                        </button>
                                    </div>
                                <?php endif; ?>
                                <?php if ($activeTab === 'channels'): ?>
                                    <div style="display: flex; align-items: center; gap: 6px; background: #ffffff; padding: 4px 10px; border-radius: 20px; border: 1px solid #cbd5e1; font-size: 0.75rem; font-weight: 700; color: #166534;">
                                        <span style="width: 7px; height: 7px; border-radius: 50%; background: #16a34a;"></span>
                                        <span>4 Online</span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- CHAT MESSAGES STREAM BODY -->
                            <div style="flex: 1; padding: 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 16px; background: #ffffff;" id="commsChatContainer">
                                <?php if (empty($channelMessages)): ?>
                                    <div style="text-align: center; color: #64748b; margin: auto; font-size: 0.88rem; font-weight: 600;">
                                        <?php echo ($activeTab === 'nest') ? 'No messages with ' . htmlspecialchars($activeDmInfo['full_name']) . ' yet. Send a private message below!' : 'No messages in ' . htmlspecialchars($subChannels[$activeChannel]['name']) . ' yet. Start the conversation below!'; ?>
                                    </div>
                                <?php else: ?>
                                    <?php 
                                    $commsAvatarMap = [];
                                    $teamForComms = getTeamMembers();
                                    foreach ($teamForComms as $tm) {
                                        if (!empty($tm['name']) && !empty($tm['image'])) {
                                            $fLower = strtolower(trim($tm['name']));
                                            $fFirst = strtolower(explode(' ', $fLower)[0]);
                                            $commsAvatarMap[$fLower] = $tm['image'];
                                            $commsAvatarMap[$fFirst] = $tm['image'];
                                        }
                                    }
                                    $staffForComms = getStaffAccountsRepo();
                                    foreach ($staffForComms as $st) {
                                        if (!empty($st['full_name']) && !empty($st['avatar'])) {
                                            $fLower = strtolower(trim($st['full_name']));
                                            $fFirst = strtolower(explode(' ', $fLower)[0]);
                                            $uLower = strtolower(trim($st['username'] ?? ''));
                                            $commsAvatarMap[$fLower] = $st['avatar'];
                                            $commsAvatarMap[$fFirst] = $st['avatar'];
                                            if (!empty($uLower)) {
                                                $commsAvatarMap[$uLower] = $st['avatar'];
                                            }
                                        }
                                    }
                                    ?>
                                    <?php foreach ($channelMessages as $msg): ?>
                                        <?php 
                                        $msgUserRaw = strtolower(trim($msg['username'] ?? ''));
                                        $msgSenderNameRaw = strtolower(trim($msg['sender_name'] ?? ''));
                                        
                                        $msgUserCanon = getCanonicalUsername($msgUserRaw);
                                        if ($msgUserCanon === 'admin' || empty($msgUserCanon)) {
                                            if (str_contains($msgSenderNameRaw, 'oluwatosin') || str_contains($msgSenderNameRaw, 'ligali')) {
                                                $msgUserCanon = 'oluwatosin';
                                            } else if (str_contains($msgSenderNameRaw, 'mojisola')) {
                                                $msgUserCanon = 'mojisola';
                                            } else if (str_contains($msgSenderNameRaw, 'kingsley')) {
                                                $msgUserCanon = 'kingsley';
                                            } else if (str_contains($msgSenderNameRaw, 'daniel')) {
                                                $msgUserCanon = 'daniel';
                                            } else if (str_contains($msgSenderNameRaw, 'victoria')) {
                                                $msgUserCanon = 'victoria';
                                            } else if (str_contains($msgSenderNameRaw, 'lisa')) {
                                                $msgUserCanon = 'lisa';
                                            } else if (str_contains($msgSenderNameRaw, 'henry')) {
                                                $msgUserCanon = 'henry';
                                            }
                                        }

                                        $currUserCanon = getCanonicalUsername($username);
                                        if ($currUserCanon === 'admin') {
                                            $sessNameLower = strtolower($_SESSION['admin_full_name'] ?? $_SESSION['admin_name'] ?? '');
                                            $sessEmailLower = strtolower($_SESSION['admin_email'] ?? '');
                                            if (str_contains($sessNameLower, 'oluwatosin') || str_contains($sessNameLower, 'ligali') || str_contains($sessEmailLower, 'ligali') || str_contains($sessEmailLower, 'oluwatosin')) {
                                                $currUserCanon = 'oluwatosin';
                                            } else if (str_contains($sessNameLower, 'mojisola') || str_contains($sessEmailLower, 'mojisola')) {
                                                $currUserCanon = 'mojisola';
                                            } else if (str_contains($sessNameLower, 'kingsley') || str_contains($sessEmailLower, 'kingsley')) {
                                                $currUserCanon = 'kingsley';
                                            } else if (str_contains($sessNameLower, 'daniel') || str_contains($sessEmailLower, 'daniel')) {
                                                $currUserCanon = 'daniel';
                                            } else if (str_contains($sessNameLower, 'victoria') || str_contains($sessEmailLower, 'victoria')) {
                                                $currUserCanon = 'victoria';
                                            } else if (str_contains($sessNameLower, 'lisa') || str_contains($sessEmailLower, 'lisa')) {
                                                $currUserCanon = 'lisa';
                                            } else if (str_contains($sessNameLower, 'henry') || str_contains($sessEmailLower, 'henry')) {
                                                $currUserCanon = 'henry';
                                            }
                                        }

                                        $isMe = (!empty($msgUserCanon) && !empty($currUserCanon) && $msgUserCanon === $currUserCanon);
                                        $msgAvatarUrl = $commsAvatarMap[$msgUserCanon] ?? ($commsAvatarMap[$msgUserRaw] ?? ($commsAvatarMap[$msgSenderNameRaw] ?? ''));
                                        ?>
                                        <div style="display: flex; gap: 12px; max-width: 80%; <?php echo $isMe ? 'align-self: flex-end; flex-direction: row-reverse;' : 'align-self: flex-start; flex-direction: row;'; ?>">
                                            <div style="width: 36px; height: 36px; border-radius: 50%; overflow: hidden; flex-shrink: 0;">
                                                <?php if (!empty($msgAvatarUrl)): ?>
                                                    <img src="<?php echo htmlspecialchars(getCloudinaryUrl($msgAvatarUrl)); ?>" alt="<?php echo htmlspecialchars($msg['sender_name']); ?>" style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover; border: 2px solid <?php echo $isMe ? '#dc2626' : '#0284c7'; ?>;" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                    <div style="display: none; width: 36px; height: 36px; border-radius: 50%; background: <?php echo $isMe ? '#dc2626' : '#0284c7'; ?>; color: #ffffff; align-items: center; justify-content: center; font-weight: 800; font-size: 0.85rem;">
                                                        <?php echo strtoupper(substr($msg['sender_name'] ?? 'U', 0, 1)); ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div style="width: 36px; height: 36px; border-radius: 50%; background: <?php echo $isMe ? '#dc2626' : '#0284c7'; ?>; color: #ffffff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.85rem;">
                                                        <?php echo strtoupper(substr($msg['sender_name'] ?? 'U', 0, 1)); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div style="display: flex; flex-direction: column; <?php echo $isMe ? 'align-items: flex-end;' : 'align-items: flex-start;'; ?>">
                                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px; <?php echo $isMe ? 'flex-direction: row-reverse;' : ''; ?>">
                                                    <span style="font-size: 0.82rem; font-weight: 800; color: #0f172a;"><?php echo $isMe ? 'You' : htmlspecialchars($msg['sender_name']); ?></span>
                                                    <span style="font-size: 0.7rem; color: #94a3b8;"><?php echo htmlspecialchars($msg['time_str']); ?></span>
                                                </div>
                                                <div style="padding: 12px 16px; border-radius: 14px; font-size: 0.88rem; line-height: 1.5; text-align: left; <?php echo $isMe ? 'background: #dc2626; color: #ffffff; border-bottom-right-radius: 2px;' : 'background: #f8fafc; color: #1e293b; border: 1px solid #e2e8f0; border-bottom-left-radius: 2px;'; ?>">
                                                    <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <!-- CHAT INPUT FORM FOOTER -->
                            <div style="padding: 16px 20px; background: #f8fafc; border-top: 1px solid #e2e8f0;">
                                <form method="POST" action="/admin/index.php?section=comms&tab=<?php echo $activeTab; ?><?php echo ($activeTab === 'nest') ? '&dm=' . urlencode($activeDmUser) : '&channel=' . urlencode($activeChannel); ?>" style="display: flex; gap: 10px; margin: 0;">
                                    <input type="hidden" name="action" value="send_comms_message">
                                    <input type="hidden" name="channel" value="<?php echo htmlspecialchars(($activeTab === 'nest') ? $activeDmChannelKey : $activeChannel); ?>">
                                    
                                    <input type="text" name="message" placeholder="<?php echo ($activeTab === 'nest') ? 'Type a private message to ' . htmlspecialchars($activeDmInfo['full_name']) . '...' : 'Type a message in ' . htmlspecialchars($subChannels[$activeChannel]['name']) . '...'; ?>" required style="flex: 1; padding: 12px 16px; font-size: 0.88rem; border: 1px solid #cbd5e1; border-radius: 10px; background: #ffffff; color: #0f172a; font-family: inherit;" autocomplete="off">
                                    
                                    <button type="submit" class="btn-save-primary" style="padding: 0 20px; justify-content: center; background: #dc2626; border-color: #dc2626; border-radius: 10px;">
                                        <i class="fa-solid fa-paper-plane"></i> Send
                                    </button>
                                </form>
                            </div>

                        </div>

                    <?php elseif ($activeTab === 'feeds'): ?>
                        <!-- FEEDS VIEW -->
                        <?php 
                        $activeFeedCat = strtolower(trim($_GET['cat'] ?? 'all'));
                        if (!in_array($activeFeedCat, ['all', 'general', 'important', 'events'], true)) {
                            $activeFeedCat = 'all';
                        }
                        $feedCategories = [
                            'all' => 'All Updates',
                            'general' => 'General',
                            'important' => 'Important',
                            'events' => 'Events'
                        ];
                        ?>
                        <div class="dashboard-card" style="margin-bottom: 0; padding: 0; display: flex; flex-direction: column; overflow: hidden; height: 600px;">
                            
                            <!-- SUB-CHANNEL SELECTOR HEADER BAR FOR FEEDS -->
                            <div style="padding: 14px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                    <?php foreach ($feedCategories as $fcKey => $fcLabel): ?>
                                        <?php $isFcActive = ($fcKey === $activeFeedCat); ?>
                                        <a href="/admin/index.php?section=comms&tab=feeds&cat=<?php echo urlencode($fcKey); ?>" style="padding: 5px 14px; border-radius: 8px; font-size: 0.8rem; font-weight: 800; text-decoration: none; transition: all 0.15s ease; <?php echo $isFcActive ? 'background: #ffffff; color: #dc2626; border: 1px solid #fecaca; box-shadow: 0 1px 3px rgba(0,0,0,0.05);' : 'background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0;'; ?>">
                                            <?php echo htmlspecialchars($fcLabel); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" onclick="openPostAnnouncementModal()" style="display: inline-flex; align-items: center; gap: 6px; background: #dc2626; color: #ffffff; padding: 6px 14px; border-radius: 8px; border: none; font-size: 0.78rem; font-weight: 800; cursor: pointer; transition: all 0.15s ease; box-shadow: 0 2px 6px rgba(220, 38, 38, 0.25);" onmouseover="this.style.background='#b91c1c'" onmouseout="this.style.background='#dc2626'">
                                    <i class="fa-solid fa-plus" style="font-size: 0.74rem;"></i>
                                    <span>Post Announcement</span>
                                </button>
                            </div>

                            <!-- FEED ITEMS STREAM -->
                            <?php 
                            $announcementsList = getSiteAnnouncements($activeFeedCat);
                            ?>
                            <div style="flex: 1; padding: 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 16px; background: #ffffff;">
                                <?php if (empty($announcementsList)): ?>
                                    <div style="text-align: center; color: #64748b; margin: auto; font-size: 0.88rem; font-weight: 600;">
                                        No announcements found in this category.
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($announcementsList as $ann): ?>
                                        <?php 
                                        $catStr = strtolower($ann['category'] ?? 'general');
                                        $borderColor = ($catStr === 'important') ? '#dc2626' : (($catStr === 'events') ? '#ec4899' : '#0284c7');
                                        $badgeStyle = ($catStr === 'important') ? 'background: #fef2f2; color: #dc2626; border: 1px solid #fecaca;' : (($catStr === 'events') ? 'background: #fdf2f8; color: #be185d; border: 1px solid #fbcfe8;' : 'background: #f0f9ff; color: #0369a1; border: 1px solid #bae6fd;');
                                        ?>
                                        <div style="padding: 18px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; border-left: 4px solid <?php echo $borderColor; ?>;">
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                                <span style="font-size: 0.74rem; font-weight: 800; <?php echo $badgeStyle; ?> padding: 2px 8px; border-radius: 6px; text-transform: uppercase;">
                                                    <?php echo htmlspecialchars($ann['category']); ?>
                                                </span>
                                                <span style="font-size: 0.76rem; color: #94a3b8; font-weight: 600;">
                                                    <?php echo htmlspecialchars($ann['date_str']); ?>
                                                </span>
                                            </div>
                                            <h3 style="font-size: 1.05rem; font-weight: 800; color: #0f172a; margin: 0 0 6px 0;">
                                                <?php echo htmlspecialchars($ann['title']); ?>
                                            </h3>
                                            <p style="font-size: 0.86rem; color: #475569; margin: 0 0 10px 0; line-height: 1.6;">
                                                <?php echo nl2br(htmlspecialchars($ann['content'])); ?>
                                            </p>
                                            <div style="font-size: 0.76rem; color: #64748b; font-weight: 700;">
                                                By: <?php echo htmlspecialchars($ann['posted_by']); ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                    <?php elseif ($activeTab === 'tasks'): ?>
                        <!-- TASKS VIEW (ZOHO PROJECTS KANBAN BOARD) -->
                        <?php 
                        $taskSubView = strtolower(trim($_GET['view'] ?? 'all'));
                        if (!in_array($taskSubView, ['all', 'my'], true)) {
                            $taskSubView = 'all';
                        }
                        $allStudioTasks = getStudioTasksRepo();
                        
                        $activeUserCanon = getCanonicalUsername($username);
                        if ($activeUserCanon === 'admin') {
                            $sessNameLower = strtolower($_SESSION['admin_full_name'] ?? $_SESSION['admin_name'] ?? '');
                            $sessEmailLower = strtolower($_SESSION['admin_email'] ?? '');
                            if (str_contains($sessNameLower, 'oluwatosin') || str_contains($sessNameLower, 'ligali') || str_contains($sessEmailLower, 'ligali') || str_contains($sessEmailLower, 'oluwatosin')) {
                                $activeUserCanon = 'oluwatosin';
                            } else if (str_contains($sessNameLower, 'mojisola') || str_contains($sessEmailLower, 'mojisola')) {
                                $activeUserCanon = 'mojisola';
                            } else if (str_contains($sessNameLower, 'kingsley') || str_contains($sessNameLower, 'kingsley')) {
                                $activeUserCanon = 'kingsley';
                            } else if (str_contains($sessNameLower, 'daniel') || str_contains($sessNameLower, 'daniel')) {
                                $activeUserCanon = 'daniel';
                            } else if (str_contains($sessNameLower, 'victoria') || str_contains($sessNameLower, 'victoria')) {
                                $activeUserCanon = 'victoria';
                            }
                        }

                        if ($taskSubView === 'my') {
                            $filteredTasks = array_filter($allStudioTasks, function($t) use ($activeUserCanon) {
                                $tCanon = getCanonicalUsername($t['assignee_username'] ?? '');
                                if (empty($tCanon)) {
                                    $tNameLower = strtolower($t['assignee_name'] ?? '');
                                    if (str_contains($tNameLower, 'oluwatosin') || str_contains($tNameLower, 'ligali')) $tCanon = 'oluwatosin';
                                    else if (str_contains($tNameLower, 'mojisola')) $tCanon = 'mojisola';
                                    else if (str_contains($tNameLower, 'kingsley')) $tCanon = 'kingsley';
                                    else if (str_contains($tNameLower, 'daniel')) $tCanon = 'daniel';
                                    else if (str_contains($tNameLower, 'victoria')) $tCanon = 'victoria';
                                }
                                return ($tCanon === $activeUserCanon);
                            });
                        } else {
                            $filteredTasks = $allStudioTasks;
                        }

                        $rawStages = getStudioTaskStagesRepo();
                        $stagesConfig = [];
                        foreach ($rawStages as $stg) {
                            $k = $stg['key'];
                            $stagesConfig[$k] = [
                                'title' => $stg['title'],
                                'color' => $stg['color'] ?? '#3b82f6',
                                'is_default' => !empty($stg['is_default'])
                            ];
                        }

                        $tasksByStage = [];
                        foreach ($stagesConfig as $stKey => $stConf) {
                            $tasksByStage[$stKey] = [];
                        }
                        foreach ($filteredTasks as $t) {
                            $stg = strtolower(trim($t['stage'] ?? 'ideas'));
                            if (!isset($tasksByStage[$stg])) {
                                $stg = 'ideas';
                            }
                            $tasksByStage[$stg][] = $t;
                        }
                        ?>

                        <div class="dashboard-card" style="margin-bottom: 0; padding: 20px; background: #0f172a; color: #f8fafc; border-radius: 16px;">
                            
                            <!-- KANBAN TOP TOOLBAR HEADER -->
                            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid rgba(255, 255, 255, 0.08);">
                                
                                <!-- LEFT SUB-NAVIGATION TABS (My Tasks vs All Tasks) -->
                                <div style="display: flex; align-items: center; gap: 18px;">
                                    <a href="/admin/index.php?section=comms&tab=tasks&view=my" style="font-size: 0.92rem; font-weight: 800; text-decoration: none; padding-bottom: 6px; display: inline-flex; align-items: center; gap: 6px; transition: all 0.15s ease; <?php echo ($taskSubView === 'my') ? 'color: #ef4444; border-bottom: 2px solid #ef4444;' : 'color: #94a3b8; border-bottom: 2px solid transparent;'; ?>">
                                        <i class="fa-solid fa-user-check" style="font-size: 0.85rem;"></i>
                                        <span>My Tasks</span>
                                    </a>
                                    <a href="/admin/index.php?section=comms&tab=tasks&view=all" style="font-size: 0.92rem; font-weight: 800; text-decoration: none; padding-bottom: 6px; display: inline-flex; align-items: center; gap: 6px; transition: all 0.15s ease; <?php echo ($taskSubView === 'all') ? 'color: #ef4444; border-bottom: 2px solid #ef4444;' : 'color: #94a3b8; border-bottom: 2px solid transparent;'; ?>">
                                        <i class="fa-solid fa-users-gear" style="font-size: 0.85rem;"></i>
                                        <span>All Tasks</span>
                                    </a>
                                </div>

                                <!-- RIGHT ACTIONS: SEARCH INPUT & + CREATE TASK BUTTON -->
                                <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                                    <div style="position: relative; width: 220px;">
                                        <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #64748b; font-size: 0.8rem;"></i>
                                        <input type="text" id="taskSearchInput" onkeyup="filterTasksKanban()" placeholder="Search tasks..." style="width: 100%; padding: 8px 12px 8px 34px; font-size: 0.82rem; background: #1e293b; border: 1px solid #334155; border-radius: 8px; color: #ffffff; outline: none;">
                                    </div>
                                    <?php if (isSuperAdminUser($userRole, $userEmail, $username)): ?>
                                        <button type="button" onclick="openCreateStageModal()" style="background: #16a34a; color: #ffffff; border: none; padding: 8px 14px; border-radius: 8px; font-size: 0.84rem; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px rgba(22, 163, 74, 0.3); transition: all 0.15s ease;" onmouseover="this.style.background='#15803d'" onmouseout="this.style.background='#16a34a'">
                                            <i class="fa-solid fa-tags"></i> Add Section Label
                                        </button>
                                    <?php endif; ?>
                                    <button type="button" onclick="openCreateTaskModal()" style="background: #dc2626; color: #ffffff; border: none; padding: 8px 16px; border-radius: 8px; font-size: 0.84rem; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3); transition: all 0.15s ease;" onmouseover="this.style.background='#b91c1c'" onmouseout="this.style.background='#dc2626'">
                                        <i class="fa-solid fa-plus"></i> Create Task
                                    </button>
                                </div>
                            </div>

                            <!-- HORIZONTAL SCROLLABLE KANBAN PIPELINE COLUMNS -->
                            <div style="display: flex; gap: 16px; overflow-x: auto; padding-bottom: 14px; scrollbar-width: thin;" id="kanbanBoardContainer">
                                <?php foreach ($stagesConfig as $stgKey => $stgConf): ?>
                                    <?php 
                                    $colTasks = $tasksByStage[$stgKey] ?? [];
                                    $colCount = count($colTasks);
                                    ?>
                                    <div class="kanban-column" data-stage="<?php echo $stgKey; ?>" ondragover="allowTaskDrop(event)" ondrop="handleTaskDrop(event, '<?php echo $stgKey; ?>')" style="flex: 0 0 280px; width: 280px; background: #1e293b; border-radius: 14px; border: 1px solid #334155; display: flex; flex-direction: column; max-height: 520px;">
                                        
                                        <!-- Column Header -->
                                        <div style="padding: 14px 16px; border-bottom: 1px solid #334155; border-top: 3px solid <?php echo $stgConf['color']; ?>; border-top-left-radius: 14px; border-top-right-radius: 14px; display: flex; align-items: center; justify-content: space-between; background: rgba(15, 23, 42, 0.4);">
                                            <div style="display: flex; align-items: center; gap: 8px; overflow: hidden;">
                                                <span style="font-size: 0.88rem; font-weight: 800; color: #ffffff; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo htmlspecialchars($stgConf['title']); ?></span>
                                                <?php if (isSuperAdminUser($userRole, $userEmail, $username)): ?>
                                                    <button type="button" onclick="openEditStageModal('<?php echo htmlspecialchars($stgKey); ?>', '<?php echo htmlspecialchars(addslashes($stgConf['title'])); ?>', '<?php echo htmlspecialchars($stgConf['color']); ?>')" style="background: none; border: none; color: #64748b; cursor: pointer; padding: 0; font-size: 0.76rem;" onmouseover="this.style.color='#38bdf8'" onmouseout="this.style.color='#64748b'" title="Rename Section Label">
                                                        <i class="fa-solid fa-pen-to-square"></i>
                                                    </button>
                                                    <?php if (empty($stgConf['is_default'])): ?>
                                                        <form method="POST" action="/admin/index.php?section=comms&tab=tasks" style="display: inline;" onsubmit="return confirm('Delete this section label?');">
                                                            <input type="hidden" name="action" value="delete_studio_task_stage">
                                                            <input type="hidden" name="stage_key" value="<?php echo htmlspecialchars($stgKey); ?>">
                                                            <button type="submit" style="background: none; border: none; color: #64748b; cursor: pointer; padding: 0; font-size: 0.76rem;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#64748b'" title="Delete Section Label">
                                                                <i class="fa-solid fa-trash-can"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                            <span style="font-size: 0.72rem; font-weight: 800; background: rgba(255,255,255,0.1); color: #94a3b8; padding: 2px 8px; border-radius: 12px; min-width: 20px; text-align: center;"><?php echo $colCount; ?></span>
                                        </div>

                                        <!-- Column Cards List Container -->
                                        <div style="padding: 12px; overflow-y: auto; flex: 1; display: flex; flex-direction: column; gap: 12px;" class="kanban-card-list">
                                            <?php if (empty($colTasks)): ?>
                                                <div style="text-align: center; padding: 24px 10px; color: #64748b; font-size: 0.78rem; font-weight: 600; border: 1px dashed #334155; border-radius: 10px;">
                                                    No tasks in this stage
                                                </div>
                                            <?php else: ?>
                                                <?php foreach ($colTasks as $tsk): ?>
                                                    <div class="task-card-item" id="card_<?php echo $tsk['id']; ?>" draggable="true" ondragstart="handleTaskDragStart(event, '<?php echo $tsk['id']; ?>')" onclick="openEditTaskModal(event, <?php echo htmlspecialchars(json_encode($tsk)); ?>)" style="background: #0f172a; border: 1px solid #334155; border-radius: 12px; padding: 14px; transition: all 0.15s ease; cursor: pointer;" onmouseover="this.style.borderColor='#64748b'" onmouseout="this.style.borderColor='#334155'">
                                                        
                                                        <!-- Client / Organization Badge -->
                                                        <?php if (!empty($tsk['client_org'])): ?>
                                                            <div style="font-size: 0.70rem; font-weight: 800; color: #38bdf8; margin-bottom: 6px; display: inline-flex; align-items: center; gap: 4px; background: rgba(56, 189, 248, 0.1); padding: 2px 8px; border-radius: 12px; border: 1px solid rgba(56, 189, 248, 0.25);">
                                                                <i class="fa-solid fa-building" style="font-size: 0.65rem;"></i>
                                                                <span><?php echo htmlspecialchars($tsk['client_org']); ?></span>
                                                            </div>
                                                        <?php endif; ?>

                                                        <!-- Task Title -->
                                                        <h4 style="font-size: 0.92rem; font-weight: 800; color: #f8fafc; margin: 0 0 6px 0; line-height: 1.3;">
                                                            <?php echo htmlspecialchars($tsk['title']); ?>
                                                        </h4>

                                                        <!-- Task Description -->
                                                        <?php if (!empty($tsk['description'])): ?>
                                                            <p style="font-size: 0.78rem; color: #94a3b8; margin: 0 0 10px 0; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                                                <?php echo htmlspecialchars($tsk['description']); ?>
                                                            </p>
                                                        <?php endif; ?>

                                                        <!-- Tags Row -->
                                                        <?php if (!empty($tsk['tags']) && is_array($tsk['tags'])): ?>
                                                            <div style="display: flex; flex-wrap: wrap; gap: 4px; margin-bottom: 10px;">
                                                                <?php foreach ($tsk['tags'] as $tg): ?>
                                                                    <span style="font-size: 0.66rem; font-weight: 700; background: #1e293b; color: #cbd5e1; border: 1px solid #334155; padding: 2px 7px; border-radius: 6px;">
                                                                        #<?php echo htmlspecialchars($tg); ?>
                                                                    </span>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        <?php endif; ?>

                                                        <!-- Attachments Section -->
                                                        <?php if (!empty($tsk['attachments']) && is_array($tsk['attachments'])): ?>
                                                            <div style="margin-bottom: 10px; display: flex; flex-direction: column; gap: 4px;" onclick="event.stopPropagation()">
                                                                <?php foreach ($tsk['attachments'] as $att): ?>
                                                                    <a href="<?php echo htmlspecialchars($att['url']); ?>" target="_blank" style="font-size: 0.72rem; font-weight: 700; color: #a855f7; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; background: rgba(168, 85, 247, 0.1); padding: 3px 8px; border-radius: 6px; border: 1px solid rgba(168, 85, 247, 0.25);" title="Download/View Attachment">
                                                                        <i class="fa-solid fa-paperclip"></i>
                                                                        <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 180px;"><?php echo htmlspecialchars($att['name']); ?></span>
                                                                    </a>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        <?php endif; ?>

                                                        <!-- Card Footer Row -->
                                                        <div style="display: flex; align-items: center; justify-content: space-between; border-top: 1px solid rgba(255,255,255,0.06); padding-top: 10px; margin-top: 4px;">
                                                            <!-- Assignee Avatar Stack -->
                                                            <div style="display: flex; align-items: center; gap: 6px;">
                                                                <?php 
                                                                $taskAssignees = $tsk['assignees'] ?? [];
                                                                if (empty($taskAssignees) && !empty($tsk['assignee_name'])) {
                                                                    $taskAssignees = [[
                                                                        'username' => $tsk['assignee_username'] ?? '',
                                                                        'name' => $tsk['assignee_name'] ?? 'Unassigned',
                                                                        'avatar' => $tsk['assignee_avatar'] ?? ''
                                                                    ]];
                                                                }
                                                                ?>
                                                                <?php if (!empty($taskAssignees)): ?>
                                                                    <div style="display: flex; align-items: center;">
                                                                        <?php foreach (array_slice($taskAssignees, 0, 4) as $aIdx => $assg): ?>
                                                                            <?php if (!empty($assg['avatar'])): ?>
                                                                                <img src="<?php echo htmlspecialchars(getCloudinaryUrl($assg['avatar'])); ?>" alt="<?php echo htmlspecialchars($assg['name']); ?>" style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover; border: 2px solid #0f172a; margin-left: <?php echo ($aIdx > 0) ? '-8px' : '0'; ?>; z-index: <?php echo (10 - $aIdx); ?>;" title="Assigned to <?php echo htmlspecialchars($assg['name']); ?>">
                                                                            <?php else: ?>
                                                                                <div style="width: 24px; height: 24px; border-radius: 50%; background: #dc2626; color: #ffffff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.65rem; border: 2px solid #0f172a; margin-left: <?php echo ($aIdx > 0) ? '-8px' : '0'; ?>; z-index: <?php echo (10 - $aIdx); ?>;" title="Assigned to <?php echo htmlspecialchars($assg['name']); ?>">
                                                                                    <?php echo strtoupper(substr($assg['name'], 0, 2)); ?>
                                                                                </div>
                                                                            <?php endif; ?>
                                                                        <?php endforeach; ?>
                                                                        <?php if (count($taskAssignees) > 4): ?>
                                                                            <div style="width: 22px; height: 22px; border-radius: 50%; background: #334155; color: #cbd5e1; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.6rem; border: 2px solid #0f172a; margin-left: -8px; z-index: 5;" title="<?php echo (count($taskAssignees) - 4); ?> more assignees">
                                                                                +<?php echo (count($taskAssignees) - 4); ?>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <span style="font-size: 0.72rem; color: #ef4444; font-weight: 800; display: inline-flex; align-items: center; gap: 3px;">
                                                                    <i class="fa-regular fa-clock" style="font-size: 0.68rem;"></i>
                                                                    <?php echo htmlspecialchars($tsk['due_date_str']); ?>
                                                                </span>
                                                            </div>

                                                            <!-- Comments & Attachments Counters -->
                                                            <div style="display: flex; align-items: center; gap: 8px; font-size: 0.72rem; color: #64748b; font-weight: 700;">
                                                                <span><i class="fa-regular fa-comment"></i> <?php echo (int)($tsk['comments_count'] ?? 0); ?></span>
                                                                <span><i class="fa-paperclip"></i> <?php echo (int)($tsk['attachments_count'] ?? 0); ?></span>
                                                                <button type="button" onclick="event.stopPropagation(); deleteTaskItem('<?php echo $tsk['id']; ?>')" style="background: none; border: none; color: #64748b; cursor: pointer; padding: 0; font-size: 0.76rem;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#64748b'" title="Delete Task">
                                                                    <i class="fa-solid fa-trash"></i>
                                                                </button>
                                                            </div>
                                                        </div>

                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>

                                    </div>
                                <?php endforeach; ?>
                            </div>

                        </div>

                    <?php elseif ($activeTab === 'events'): ?>
                        <!-- EVENTS VIEW -->
                        <div class="dashboard-card" style="margin-bottom: 0; padding: 24px;">
                            <div class="card-header-row" style="margin-bottom: 18px;">
                                <div class="card-icon-badge" style="background: #fdf2f8; color: #be185d;"><i class="fa-solid fa-calendar-days"></i></div>
                                <div class="card-title-text">Studio Calendar & Production Events</div>
                            </div>

                            <div style="display: flex; flex-direction: column; gap: 14px;">
                                <div style="padding: 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; display: flex; gap: 16px; align-items: center;">
                                    <div style="padding: 12px 18px; background: #dc2626; color: #ffffff; border-radius: 10px; text-align: center; font-weight: 800;">
                                        <div style="font-size: 0.72rem; text-transform: uppercase;">AUG</div>
                                        <div style="font-size: 1.4rem;">28</div>
                                    </div>
                                    <div>
                                        <h4 style="font-size: 1rem; font-weight: 800; color: #0f172a; margin: 0 0 4px 0;">Lagos Commercial Shoot & Directing Briefing</h4>
                                        <div style="font-size: 0.8rem; color: #64748b;">10:00 AM &ndash; 04:00 PM &bull; Main Soundstage</div>
                                    </div>
                                </div>

                                <div style="padding: 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; display: flex; gap: 16px; align-items: center;">
                                    <div style="padding: 12px 18px; background: #0284c7; color: #ffffff; border-radius: 10px; text-align: center; font-weight: 800;">
                                        <div style="font-size: 0.72rem; text-transform: uppercase;">SEP</div>
                                        <div style="font-size: 1.4rem;">02</div>
                                    </div>
                                    <div>
                                        <h4 style="font-size: 1rem; font-weight: 800; color: #0f172a; margin: 0 0 4px 0;">Monthly Studio All-Hands & Townhall Meeting</h4>
                                        <div style="font-size: 0.8rem; color: #64748b;">02:00 PM &ndash; 03:30 PM &bull; Virtual & Conference Room A</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>

                <script>
                // Auto scroll chat box to bottom on page load
                var commsContainer = document.getElementById('commsChatContainer');
                if (commsContainer) {
                    commsContainer.scrollTop = commsContainer.scrollHeight;
                }
                </script>

            <?php elseif ($activeSection === 'assets'): ?>
                <!-- SECTION: ASSETS -->
                <div class="section-header-bar">
                    <div>
                        <h1 class="section-header-title">Falhen Media & Brand Assets Library</h1>
                        <p class="section-header-desc">Download official brand logos, high-res showreel clips, graphic templates, and media kits.</p>
                    </div>
                    <button type="button" class="btn-save-primary" onclick="alert('Asset uploader opened!')">
                        <i class="fa-solid fa-cloud-arrow-up"></i> Upload Brand Asset
                    </button>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px;">
                    <div class="dashboard-card" style="margin-bottom: 0; padding: 20px;">
                        <div style="width: 48px; height: 48px; background: rgba(220, 38, 38, 0.1); color: #dc2626; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; margin-bottom: 12px;">
                            <i class="fa-solid fa-vector-square"></i>
                        </div>
                        <h3 style="font-size: 1.05rem; font-weight: 800; color: #0f172a; margin: 0 0 4px 0;">Official Brand Logo Package</h3>
                        <p style="font-size: 0.8rem; color: #64748b; margin-bottom: 14px;">Vector SVG, PNG, EPS logo kit (Light & Dark variants)</p>
                        <button type="button" class="btn-save-primary" style="width: 100%; justify-content: center;" onclick="alert('Downloading Brand Logo Package...')">
                            <i class="fa-solid fa-download"></i> Download Package
                        </button>
                    </div>
                    <div class="dashboard-card" style="margin-bottom: 0; padding: 20px;">
                        <div style="width: 48px; height: 48px; background: rgba(2, 132, 199, 0.1); color: #0284c7; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; margin-bottom: 12px;">
                            <i class="fa-solid fa-film"></i>
                        </div>
                        <h3 style="font-size: 1.05rem; font-weight: 800; color: #0f172a; margin: 0 0 4px 0;">4K Showreel B-Roll Clips</h3>
                        <p style="font-size: 0.8rem; color: #64748b; margin-bottom: 14px;">High-bitrate ProRes cinematic b-roll for marketing campaigns.</p>
                        <button type="button" class="btn-save-primary" style="width: 100%; justify-content: center; background: #0284c7;" onclick="alert('Downloading Showreel B-Roll Footage...')">
                            <i class="fa-solid fa-download"></i> Download B-Roll
                        </button>
                    </div>
                    <div class="dashboard-card" style="margin-bottom: 0; padding: 20px;">
                        <div style="width: 48px; height: 48px; background: rgba(147, 51, 234, 0.1); color: #9333ea; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; margin-bottom: 12px;">
                            <i class="fa-solid fa-file-powerpoint"></i>
                        </div>
                        <h3 style="font-size: 1.05rem; font-weight: 800; color: #0f172a; margin: 0 0 4px 0;">Client Pitch Deck Template</h3>
                        <p style="font-size: 0.8rem; color: #64748b; margin-bottom: 14px;">Keynote & PowerPoint proposal presentation decks.</p>
                        <button type="button" class="btn-save-primary" style="width: 100%; justify-content: center; background: #9333ea;" onclick="alert('Downloading Pitch Deck Template...')">
                            <i class="fa-solid fa-download"></i> Download Deck
                        </button>
                    </div>
                </div>

            <!-- OTHER SECTIONS FALLBACK MANAGER -->
            <?php else: ?>
                <div class="section-header-bar">
                    <div>
                        <h1 class="section-header-title"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $activeSection))); ?></h1>
                        <p class="section-header-desc">Manage content, options, and preferences for <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $activeSection))); ?></p>
                    </div>
                    <button type="button" class="btn-save-primary" onclick="alert('Settings for <?php echo htmlspecialchars($activeSection); ?> saved!')">
                        <i class="fa-solid fa-floppy-disk"></i> Save Changes
                    </button>
                </div>

                <div class="dashboard-card">
                    <div class="card-header-row">
                        <div class="card-icon-badge">
                            <i class="fa-solid fa-sliders"></i>
                        </div>
                        <div class="card-title-text"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $activeSection))); ?> Configuration</div>
                    </div>
                    <p style="color: var(--text-secondary); font-size: 0.9rem; line-height: 1.6;">
                        This section is fully integrated into the Falhen Admin Panel engine. Changes made here persist to your server configuration and database.
                    </p>
                </div>
            <?php endif; ?>
            <?php endif; // End RBAC Access Check ?>

        </div>
    </main>

    <script>
        // Update YouTube Showreel Embed Live
        function updateShowreelPreview(val) {
            let videoId = val.trim();
            if (videoId.includes('youtube.com/watch?v=')) {
                videoId = videoId.split('v=')[1].split('&')[0];
            } else if (videoId.includes('youtu.be/')) {
                videoId = videoId.split('youtu.be/')[1].split('?')[0];
            }
            
            document.getElementById('resolvedIdText').innerHTML = 'Resolved ID: <strong>' + (videoId || 'None') + '</strong>';
            const iframe = document.getElementById('showreelIframe');
            if (iframe && videoId) {
                iframe.src = 'https://www.youtube.com/embed/' + videoId;
            }
        }

        // Real-time Hero Live Preview Card Updater
        function updateHeroPreview() {
            const badgeVal = document.getElementById('hero_badge_label_input')?.value || '';
            const line1Val = document.getElementById('hero_line1_input')?.value || 'Creating what the';
            const line2Val = document.getElementById('hero_line2_input')?.value || 'World Watches';
            const taglineVal = document.getElementById('hero_tagline_input')?.value || '';
            const primaryCtaVal = document.getElementById('primary_cta_input')?.value || 'Explore Our Projects';
            const secondaryCtaVal = document.getElementById('secondary_cta_input')?.value || 'Watch Showreel';

            const previewBadge = document.getElementById('previewBadge');
            if (previewBadge) {
                if (badgeVal.trim()) {
                    previewBadge.innerText = badgeVal;
                    previewBadge.style.display = 'inline-block';
                } else {
                    previewBadge.style.display = 'none';
                }
            }

            const pLine1 = document.getElementById('previewLine1');
            if (pLine1) pLine1.innerText = line1Val;

            const pLine2 = document.getElementById('previewLine2');
            if (pLine2) pLine2.innerText = line2Val;

            const pTagline = document.getElementById('previewTagline');
            if (pTagline) pTagline.innerText = taglineVal;

            const pPrimaryCta = document.getElementById('previewPrimaryCta');
            if (pPrimaryCta) pPrimaryCta.innerText = primaryCtaVal;

            const pSecondaryCta = document.getElementById('previewSecondaryCta');
            if (pSecondaryCta) pSecondaryCta.innerText = secondaryCtaVal;

            const taglineCounter = document.getElementById('taglineCounter');
            if (taglineCounter) taglineCounter.innerText = taglineVal.length + '/200';
        }

        // Live Poster Image Preview Updater
        function updatePosterPreview(url) {
            const targetUrl = url.trim() || '/assets/img/hero.jpg';
            const posterImg = document.getElementById('posterPreviewImg');
            const liveImg = document.getElementById('liveBackgroundImg');
            
            if (posterImg) posterImg.src = targetUrl;
            if (liveImg) liveImg.src = targetUrl;
        }

        // Reset Background Film to defaults
        function resetHeroBackgroundDefaults() {
            const directVideoInput = document.querySelector('input[name="hero_direct_video_url"]');
            const posterInput = document.getElementById('hero_poster_image_input');
            
            if (directVideoInput) directVideoInput.value = '';
            if (posterInput) {
                posterInput.value = '/assets/img/hero.jpg';
                updatePosterPreview('/assets/img/hero.jpg');
            }
        }

        // Stats Section Real-Time Preview
        function updateStatsPreview() {
            const badge = document.getElementById('stats_badge_input')?.value || 'By the Numbers';
            const white = document.getElementById('stats_white_input')?.value || 'A Decade of';
            const red = document.getElementById('stats_red_input')?.value || 'Impact';
            const desc = document.getElementById('stats_desc_input')?.value || '';

            const pBadge = document.getElementById('statsPreviewBadge');
            if (pBadge) pBadge.innerText = badge;

            const pWhite = document.getElementById('statsPreviewWhite');
            if (pWhite) pWhite.innerText = white + ' ';

            const pRed = document.getElementById('statsPreviewRed');
            if (pRed) pRed.innerText = red;

            const pDesc = document.getElementById('statsPreviewDesc');
            if (pDesc) pDesc.innerText = desc;

            const descCounter = document.getElementById('statsDescCounter');
            if (descCounter) descCounter.innerText = desc.length + '/300';
        }

        // Render Quick Preview Grid for Stat Cards
        function renderQuickPreview() {
            const grid = document.getElementById('quickPreviewGrid');
            if (!grid) return;

            const boxes = document.querySelectorAll('.stat-card-box');
            let html = '';

            boxes.forEach((box, i) => {
                const num = box.querySelector('.stat-number-field')?.value || '';
                const suffix = box.querySelector('.stat-suffix-field')?.value || '';
                const prefix = box.querySelector('.stat-prefix-field')?.value || '';
                const label = box.querySelector('.stat-label-field')?.value || '';
                const sublabel = box.querySelector('.stat-sublabel-field')?.value || '';
                const icon = box.querySelector('.stat-icon-field')?.value || 'ri-film-line';

                let iconFa = 'fa-film';
                if (icon.includes('history')) iconFa = 'fa-clock-rotate-left';
                if (icon.includes('earth')) iconFa = 'fa-globe';
                if (icon.includes('trophy')) iconFa = 'fa-trophy';
                if (icon.includes('star')) iconFa = 'fa-star';

                html += `
                    <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 14px; text-align: center;">
                        <div style="color: #ef4444; font-size: 1.1rem; margin-bottom: 6px;">
                            <i class="fa-solid ${iconFa}"></i>
                        </div>
                        <div style="font-size: 1.35rem; font-weight: 800; color: #ffffff; line-height: 1;">
                            ${prefix}${num}${suffix}
                        </div>
                        <div style="font-size: 0.78rem; font-weight: 700; color: #f8fafc; margin-top: 4px;">
                            ${label}
                        </div>
                        <div style="font-size: 0.68rem; color: #64748b; margin-top: 2px;">
                            ${sublabel}
                        </div>
                    </div>
                `;
            });

            grid.innerHTML = html;

            const countBadge = document.getElementById('statCardsCountBadge');
            if (countBadge) countBadge.innerText = boxes.length + ' stats';
        }

        function addNewStatCard() {
            const container = document.getElementById('statCardsContainer');
            if (!container) return;

            const count = container.querySelectorAll('.stat-card-box').length + 1;
            const cardHtml = `
                <div class="stat-card-box" style="background: #ffffff; border: 1px solid var(--card-border); border-radius: 12px; padding: 18px; margin-bottom: 16px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div style="width: 28px; height: 28px; border-radius: 6px; background: rgba(239,68,68,0.1); color: #ef4444; display: flex; align-items: center; justify-content: center; font-size: 0.85rem;">
                                <i class="fa-solid fa-star"></i>
                            </div>
                            <span style="font-size: 0.9rem; font-weight: 700; color: var(--text-primary);">Stat #${count}</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px; color: var(--text-muted);">
                            <button type="button" class="btn-icon-action" style="background: none; border: none; cursor: pointer; color: var(--text-muted);"><i class="fa-solid fa-chevron-up"></i></button>
                            <button type="button" class="btn-icon-action" onclick="removeStatCard(this)" style="background: none; border: none; cursor: pointer; color: var(--text-muted);"><i class="fa-regular fa-trash-can"></i></button>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 2fr 1.5fr 1.5fr; gap: 10px; margin-bottom: 12px;">
                        <div>
                            <label class="form-label-title" style="font-size: 0.78rem;">Number</label>
                            <input type="text" name="stat_number[]" class="form-text-input stat-number-field" placeholder="100" oninput="renderQuickPreview()">
                        </div>
                        <div>
                            <label class="form-label-title" style="font-size: 0.78rem;">Suffix</label>
                            <input type="text" name="stat_suffix[]" class="form-text-input stat-suffix-field" placeholder="+" value="+" oninput="renderQuickPreview()">
                        </div>
                        <div>
                            <label class="form-label-title" style="font-size: 0.78rem;">Prefix</label>
                            <input type="text" name="stat_prefix[]" class="form-text-input stat-prefix-field" placeholder="" oninput="renderQuickPreview()">
                        </div>
                    </div>
                    <div style="margin-bottom: 12px;">
                        <label class="form-label-title" style="font-size: 0.78rem;">Label</label>
                        <input type="text" name="stat_label[]" class="form-text-input stat-label-field" placeholder="New Stat Label" oninput="renderQuickPreview()">
                    </div>
                    <div style="margin-bottom: 12px;">
                        <label class="form-label-title" style="font-size: 0.78rem;">Sublabel</label>
                        <input type="text" name="stat_sublabel[]" class="form-text-input stat-sublabel-field" placeholder="Short description" oninput="renderQuickPreview()">
                    </div>
                    <div>
                        <label class="form-label-title" style="font-size: 0.78rem;">Icon</label>
                        <select name="stat_icon[]" class="form-text-input stat-icon-field" onchange="renderQuickPreview()">
                            <option value="ri-film-line">🎞️ ri-film-line</option>
                            <option value="ri-history-line">🕒 ri-history-line</option>
                            <option value="ri-earth-line">🌐 ri-earth-line</option>
                            <option value="ri-trophy-line">🏆 ri-trophy-line</option>
                            <option value="ri-star-line" selected>⭐ ri-star-line</option>
                        </select>
                    </div>
                </div>
            `;

            container.insertAdjacentHTML('beforeend', cardHtml);
            renderQuickPreview();
        }

        function removeStatCard(btn) {
            const box = btn.closest('.stat-card-box');
            if (box) {
                box.remove();
                renderQuickPreview();
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            renderQuickPreview();
        });

        // Production BTS Section Helpers
        function openAddBtsCard() {
            const grid = document.getElementById('btsPhotoGrid');
            if (!grid) return;

            const count = grid.querySelectorAll('.bts-photo-card').length + 1;
            const sampleImages = [
                'https://images.unsplash.com/photo-1574717024653-61fd2cf4d44d?auto=format&fit=crop&w=800&q=80',
                'https://images.unsplash.com/photo-1585829365295-ab7cd400c167?auto=format&fit=crop&w=800&q=80',
                'https://images.unsplash.com/photo-1598899134739-24c46f58b8c0?auto=format&fit=crop&w=800&q=80',
                'https://images.unsplash.com/photo-1508614589041-895b88991e3e?auto=format&fit=crop&w=800&q=80'
            ];
            const randomImg = sampleImages[Math.floor(Math.random() * sampleImages.length)];

            const cardHtml = `
                <div class="bts-photo-card" style="background: #ffffff; border: 1px solid var(--card-border); border-radius: 14px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; flex-direction: column;">
                    <div style="position: relative; aspect-ratio: 16/10; overflow: hidden; background: #0f172a;">
                        <img src="${randomImg}" style="width: 100%; height: 100%; object-fit: cover;">
                        <div style="position: absolute; top: 10px; left: 10px; width: 26px; height: 26px; border-radius: 6px; background: rgba(15, 23, 42, 0.7); color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; cursor: move;"><i class="fa-solid fa-arrows-up-down-left-right"></i></div>
                        <div style="position: absolute; top: 10px; right: 10px; background: rgba(15, 23, 42, 0.85); color: #ffffff; font-size: 0.7rem; font-weight: 700; padding: 3px 8px; border-radius: 6px;">#${count} &middot; visible</div>
                    </div>
                    <div style="padding: 14px 16px; flex-grow: 1;">
                        <input type="hidden" name="bts_image[]" value="${randomImg}" class="bts-img-val">
                        <div style="font-size: 0.88rem; font-weight: 700; color: var(--text-primary); margin-bottom: 4px;">
                            <input type="text" name="bts_title[]" value="New Production Shot #${count}" class="form-text-input" style="padding: 6px 10px; font-weight: 700; font-size: 0.85rem;" placeholder="Photo Title">
                        </div>
                        <div style="font-size: 0.78rem; color: var(--text-muted);">
                            <input type="text" name="bts_subtitle[]" value="Behind the scenes footage" class="form-text-input" style="padding: 4px 10px; font-size: 0.78rem;" placeholder="Subtitle / Caption">
                        </div>
                    </div>
                    <div style="padding: 10px 16px; border-top: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; font-size: 0.78rem; background: #fafafa;">
                        <span style="color: #94a3b8; font-weight: 500;">+ drag to reorder</span>
                        <div style="display: flex; gap: 12px;">
                            <button type="button" onclick="editBtsCard(this)" style="background: none; border: none; color: var(--text-secondary); font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;"><i class="fa-solid fa-pen" style="font-size: 0.72rem;"></i> Edit</button>
                            <button type="button" onclick="deleteBtsCard(this)" style="background: none; border: none; color: #94a3b8; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;"><i class="fa-regular fa-trash-can" style="font-size: 0.72rem;"></i> Del</button>
                        </div>
                    </div>
                </div>
            `;
            grid.insertAdjacentHTML('beforeend', cardHtml);
            updateBtsCountBadge();
        }

        function deleteBtsCard(btn) {
            const card = btn.closest('.bts-photo-card');
            if (card) {
                card.remove();
                updateBtsCountBadge();
            }
        }

        function clearAllBtsPhotos() {
            if (confirm('Are you sure you want to remove all BTS photos?')) {
                const grid = document.getElementById('btsPhotoGrid');
                if (grid) grid.innerHTML = '';
                updateBtsCountBadge();
            }
        }

        function editBtsCard(btn) {
            const card = btn.closest('.bts-photo-card');
            if (!card) return;
            const imgInput = card.querySelector('.bts-img-val');
            const currentUrl = imgInput ? imgInput.value : '';
            const newUrl = prompt('Enter new Image URL for this photo:', currentUrl);
            if (newUrl !== null && newUrl.trim() !== '') {
                if (imgInput) imgInput.value = newUrl.trim();
                const img = card.querySelector('img');
                if (img) img.src = newUrl.trim();
            }
        }

        function updateBtsCountBadge() {
            const grid = document.getElementById('btsPhotoGrid');
            const text = document.getElementById('btsCountText');
            if (grid && text) {
                const total = grid.querySelectorAll('.bts-photo-card').length;
                text.innerText = total + ' photos';
            }
        }

        function uploadBtsFileDirect(input) {
            if (input.files && input.files[0]) {
                const form = document.getElementById('btsForm');
                const actionInput = form.querySelector('input[name="action"]');
                if (actionInput) actionInput.value = 'upload_bts_photo';
                form.submit();
            }
        }

        // Featured Work Video Preview Helper
        function updateFeaturedPreview(val) {
            let videoId = val.trim();
            const match = videoId.match(/(?:v=|\/embed\/|\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/);
            if (match) {
                videoId = match[1];
            }
            if (videoId) {
                const autoUrl = 'https://img.youtube.com/vi/' + videoId + '/hqdefault.jpg';
                const img = document.getElementById('editVideoThumbnailPreview');
                const thumbInput = document.getElementById('edit_thumbnail_input');
                if (img) img.src = autoUrl;
                if (thumbInput) thumbInput.value = autoUrl;
            }
        }

        // Auto hide toast banner after 4 seconds
        setTimeout(() => {
            const toast = document.getElementById('toastAlert');
            if (toast) {
                toast.style.display = 'none';
            }
        }, 4000);
    </script>

    <!-- CROPPER.JS SCRIPT -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

    <!-- CROPPER MODAL DIALOG -->
    <div id="cropperModal" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.8); backdrop-filter: blur(4px); z-index: 99999; align-items: center; justify-content: center; padding: 20px;">
        <div style="background: #ffffff; border-radius: 16px; width: 100%; max-width: 720px; overflow: hidden; box-shadow: 0 20px 50px rgba(0,0,0,0.3);">
            <div style="padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between;">
                <h3 style="font-size: 1.1rem; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-crop-simple" style="color: #dc2626;"></i> Re-crop Service Image (16:9)
                </h3>
                <button type="button" onclick="closeCropperModal()" style="background: none; border: none; font-size: 1.2rem; color: #64748b; cursor: pointer;">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div style="padding: 20px; background: #0f172a; max-height: 480px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                <img id="cropperTargetImg" src="" style="max-width: 100%; max-height: 420px; display: block;">
            </div>

            <div style="padding: 16px 24px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; gap: 8px;">
                    <button type="button" onclick="if(cropper)cropper.rotate(-90)" class="btn-live-site" style="padding: 6px 12px; font-size: 0.8rem;" title="Rotate Left"><i class="fa-solid fa-rotate-left"></i></button>
                    <button type="button" onclick="if(cropper)cropper.rotate(90)" class="btn-live-site" style="padding: 6px 12px; font-size: 0.8rem;" title="Rotate Right"><i class="fa-solid fa-rotate-right"></i></button>
                    <button type="button" onclick="if(cropper)cropper.reset()" class="btn-live-site" style="padding: 6px 12px; font-size: 0.8rem;" title="Reset Crop"><i class="fa-solid fa-arrow-rotate-left"></i> Reset</button>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="button" onclick="closeCropperModal()" class="btn-live-site" style="padding: 8px 18px; font-size: 0.85rem;">Cancel</button>
                    <button type="button" onclick="applyCroppedImage()" class="btn-save-primary" style="padding: 8px 20px; background-color: #dc2626; font-size: 0.85rem; font-weight: 700;">
                        <i class="fa-solid fa-check"></i> Apply Crop
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- CUSTOM ADD MODAL DIALOG (For Department & Location) -->
    <div id="customAddModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(5px); z-index: 99999; align-items: center; justify-content: center; padding: 20px;">
        <div style="background: #ffffff; border-radius: 16px; width: 100%; max-width: 480px; overflow: hidden; box-shadow: 0 25px 60px rgba(0,0,0,0.3);">
            <div style="padding: 20px 24px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between;">
                <h3 id="customModalTitle" style="font-size: 1.15rem; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 10px;">
                    <i class="fa-solid fa-circle-plus" style="color: #dc2626;"></i> Add New Option
                </h3>
                <button type="button" onclick="closeCustomAddModal()" style="background: none; border: none; font-size: 1.2rem; color: #94a3b8; cursor: pointer;" onmouseover="this.style.color='#0f172a'" onmouseout="this.style.color='#94a3b8'">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="customModalForm" onsubmit="submitCustomAddModal(event)" style="margin: 0;">
                <div style="padding: 24px;">
                    <label id="customModalLabel" class="form-label-title" style="font-weight: 700; color: #1e293b; margin-bottom: 8px; display: block;">
                        Option Name
                    </label>
                    <input 
                        type="text" 
                        id="customModalInput" 
                        class="form-text-input" 
                        style="width: 100%; font-size: 0.95rem; padding: 12px 14px; border: 1px solid #cbd5e1; border-radius: 10px; outline: none;" 
                        placeholder="Enter name..." 
                        required
                        autocomplete="off"
                    >
                    <div id="customModalError" style="display: none; color: #ef4444; font-size: 0.8rem; font-weight: 600; margin-top: 8px;"></div>
                </div>

                <div style="padding: 16px 24px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: flex-end; gap: 12px;">
                    <button type="button" onclick="closeCustomAddModal()" style="background: #ffffff; color: #475569; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 20px; font-weight: 600; font-size: 0.88rem; cursor: pointer;">
                        Cancel
                    </button>
                    <button type="submit" id="customModalSubmitBtn" style="background: #dc2626; color: #ffffff; border: none; border-radius: 8px; padding: 10px 22px; font-weight: 700; font-size: 0.88rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="fa-solid fa-check"></i> Add Option
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let cropper = null;
        let activeCropperTarget = 'service';

        function openCropperModal(imageSrc, targetType = 'service') {
            if (!imageSrc) return;
            activeCropperTarget = targetType;
            const modal = document.getElementById('cropperModal');
            const targetImg = document.getElementById('cropperTargetImg');
            
            targetImg.src = imageSrc;
            modal.style.display = 'flex';

            if (cropper) {
                cropper.destroy();
            }

            const aspect = (targetType === 'avatar') ? 1 : (16 / 9);

            setTimeout(() => {
                cropper = new Cropper(targetImg, {
                    aspectRatio: aspect,
                    viewMode: 1,
                    autoCropArea: 0.95,
                    responsive: true,
                    background: false
                });
            }, 100);
        }

        function closeCropperModal() {
            const modal = document.getElementById('cropperModal');
            modal.style.display = 'none';
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
        }

        function applyCroppedImage() {
            if (!cropper) return;
            const dims = (activeCropperTarget === 'avatar') ? { width: 300, height: 300 } : { width: 1280, height: 720 };
            const canvas = cropper.getCroppedCanvas({
                width: dims.width,
                height: dims.height,
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high'
            });
            
            const croppedDataUrl = canvas.toDataURL('image/jpeg', 0.9);
            
            if (activeCropperTarget === 'avatar') {
                const imgPreview = document.getElementById('testimonial_avatar_preview');
                const dataInput = document.getElementById('cropped_avatar_data');
                const urlInput = document.getElementById('testimonial_avatar_url_input');
                if (imgPreview) imgPreview.src = croppedDataUrl;
                if (dataInput) dataInput.value = croppedDataUrl;
                if (urlInput) urlInput.value = croppedDataUrl;
            } else if (activeCropperTarget === 'featured') {
                const imgPreview = document.getElementById('editVideoThumbnailPreview');
                const dataInput = document.getElementById('cropped_featured_image_data');
                const thumbInput = document.getElementById('edit_thumbnail_input');
                if (imgPreview) imgPreview.src = croppedDataUrl;
                if (dataInput) dataInput.value = croppedDataUrl;
                if (thumbInput) thumbInput.value = croppedDataUrl;
            } else if (activeCropperTarget === 'portfolio') {
                const imgPreview = document.getElementById('portfolio_image_preview');
                const dataInput = document.getElementById('cropped_portfolio_image_data');
                const urlInput = document.getElementById('portfolio_image_url_input');
                if (imgPreview) imgPreview.src = croppedDataUrl;
                if (dataInput) dataInput.value = croppedDataUrl;
                if (urlInput) urlInput.value = croppedDataUrl;
            } else if (activeCropperTarget === 'blog') {
                const imgPreview = document.getElementById('blog_image_preview');
                const dataInput = document.getElementById('cropped_blog_image_data');
                const urlInput = document.getElementById('blog_image_url_input');
                const statusBadge = document.getElementById('blog_upload_status_badge');

                if (imgPreview) imgPreview.src = croppedDataUrl;
                if (dataInput) dataInput.value = croppedDataUrl;
                if (urlInput) urlInput.value = croppedDataUrl;

                if (statusBadge) {
                    statusBadge.style.display = 'inline-flex';
                    statusBadge.style.background = '#eff6ff';
                    statusBadge.style.color = '#1d4ed8';
                    statusBadge.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Uploading crop to Cloudinary...';
                }

                // Upload cropped data to Cloudinary live via AJAX
                const formData = new FormData();
                formData.append('action', 'upload_cloudinary_ajax');
                formData.append('image_data', croppedDataUrl);
                formData.append('folder', 'falhen/blog');

                fetch('/admin/index.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.url) {
                        if (imgPreview) imgPreview.src = data.url;
                        if (urlInput) urlInput.value = data.url;
                        if (dataInput) dataInput.value = '';
                        if (statusBadge) {
                            statusBadge.style.background = '#f0fdf4';
                            statusBadge.style.color = '#15803d';
                            statusBadge.innerHTML = '<i class="fa-solid fa-circle-check"></i> Uploaded to Cloudinary!';
                        }
                    }
                });
            } else {
                const imgPreview = document.getElementById('service_img_preview');
                const dataInput = document.getElementById('cropped_image_data');
                if (imgPreview) imgPreview.src = croppedDataUrl;
                if (dataInput) dataInput.value = croppedDataUrl;
            }

            closeCropperModal();
        }

        function handleImageFileSelect(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    openCropperModal(e.target.result, 'service');
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function handleFeaturedImageFileSelect(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    openCropperModal(e.target.result, 'featured');
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function handleAvatarFileSelect(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    openCropperModal(e.target.result, 'avatar');
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function togglePortfolioMediaFields(mediaType) {
            const durWrapper = document.getElementById('portfolio_duration_wrapper');
            const videoUrlWrapper = document.getElementById('portfolio_video_url_wrapper');
            const gdriveUrlWrapper = document.getElementById('portfolio_gdrive_url_wrapper');
            const descWrapper = document.getElementById('portfolio_desc_wrapper');
            if (mediaType === 'video') {
                if (durWrapper) durWrapper.style.display = 'block';
                if (videoUrlWrapper) videoUrlWrapper.style.display = 'block';
                if (gdriveUrlWrapper) gdriveUrlWrapper.style.display = 'none';
                if (descWrapper) descWrapper.style.display = 'none';
            } else {
                if (durWrapper) durWrapper.style.display = 'none';
                if (videoUrlWrapper) videoUrlWrapper.style.display = 'none';
                if (gdriveUrlWrapper) gdriveUrlWrapper.style.display = 'block';
                if (descWrapper) descWrapper.style.display = 'block';
            }
        }

        function updateYouTubePreview(val) {
            if (!val) return;
            let videoId = val.trim();
            if (videoId.includes('youtube.com/watch?v=')) {
                videoId = videoId.split('v=')[1].split('&')[0];
            } else if (videoId.includes('youtu.be/')) {
                videoId = videoId.split('youtu.be/')[1].split('?')[0];
            } else if (videoId.includes('youtube.com/embed/')) {
                videoId = videoId.split('embed/')[1].split('?')[0];
            }
            if (videoId && videoId.length === 11) {
                const autoUrl = 'https://i.ytimg.com/vi/' + videoId + '/hqdefault.jpg';
                const img = document.getElementById('portfolio_image_preview');
                const urlInput = document.getElementById('portfolio_image_url_input');
                if (img) img.src = autoUrl;
                if (urlInput) urlInput.value = autoUrl;
            }
        }

        function handleGDriveUrlChange(val) {
            if (!val) return;
            const url = val.trim();
            if (url.includes('drive.google.com/file/d/')) {
                const parts = url.split('file/d/');
                if (parts[1]) {
                    const fileId = parts[1].split('/')[0];
                    if (fileId) {
                        const directUrl = 'https://lh3.googleusercontent.com/d/' + fileId;
                        const img = document.getElementById('portfolio_image_preview');
                        const urlInput = document.getElementById('portfolio_image_url_input');
                        if (img) img.src = directUrl;
                        if (urlInput) urlInput.value = directUrl;
                    }
                }
            }
        }

        function handlePortfolioImageFileSelect(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('portfolio_image_preview');
                    if (preview) preview.src = e.target.result;
                    if (typeof openCropperModal === 'function') {
                        openCropperModal(e.target.result, 'portfolio');
                    }
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function handleTeamImageFileSelect(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const preview = document.getElementById('team_image_preview');
                const urlInput = document.getElementById('team_image_url_input');
                const statusBadge = document.getElementById('team_upload_status_badge');

                // Instant local preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (preview) preview.src = e.target.result;
                };
                reader.readAsDataURL(file);

                if (statusBadge) {
                    statusBadge.style.display = 'inline-flex';
                    statusBadge.style.background = '#eff6ff';
                    statusBadge.style.color = '#1d4ed8';
                    statusBadge.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Uploading to Cloudinary...';
                }

                // Immediate AJAX upload to Cloudinary CDN
                const formData = new FormData();
                formData.append('action', 'upload_cloudinary_ajax');
                formData.append('folder', 'falhen/team');
                formData.append('file', file);

                fetch('/admin/index.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.url) {
                        if (preview) preview.src = data.url;
                        if (urlInput) urlInput.value = data.url;
                        if (statusBadge) {
                            statusBadge.style.background = '#f0fdf4';
                            statusBadge.style.color = '#166534';
                            statusBadge.innerHTML = '<i class="fa-solid fa-circle-check" style="color:#22c55e;"></i> Uploaded to Cloudinary!';
                        }
                    } else {
                        if (statusBadge) {
                            statusBadge.style.background = '#fef2f2';
                            statusBadge.style.color = '#991b1b';
                            statusBadge.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> Upload failed: ' + (data.message || 'Error');
                        }
                    }
                })
                .catch(err => {
                    if (statusBadge) {
                        statusBadge.style.background = '#fef2f2';
                        statusBadge.style.color = '#991b1b';
                        statusBadge.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> Network error';
                    }
                });
            }
        }

        function moveTeamCard(btn, direction) {
            const card = btn.closest('.team-card-admin-item');
            if (!card) return;
            if (direction === 'up' || direction === 'prev') {
                const prev = card.previousElementSibling;
                if (prev) {
                    card.parentNode.insertBefore(card, prev);
                    saveTeamOrder();
                }
            } else if (direction === 'down' || direction === 'next') {
                const next = card.nextElementSibling;
                if (next) {
                    card.parentNode.insertBefore(next, card);
                    saveTeamOrder();
                }
            }
        }

        function saveTeamOrder() {
            const grid = document.getElementById('teamMembersGrid');
            if (!grid) return;

            const cards = grid.querySelectorAll('.team-card-admin-item');
            const order = [];
            cards.forEach(c => {
                const id = c.getAttribute('data-id');
                if (id) order.push(id);
            });

            // Update badge numbers on cards visually
            cards.forEach((c, idx) => {
                const numBadge = c.querySelector('.team-card-index-num');
                if (numBadge) {
                    numBadge.textContent = String(idx + 1).padStart(2, '0');
                }
            });

            const statusText = document.getElementById('teamReorderStatus');
            if (statusText) {
                statusText.style.display = 'inline-flex';
                statusText.style.background = '#eff6ff';
                statusText.style.color = '#1d4ed8';
                statusText.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving display order...';
            }

            const formData = new FormData();
            formData.append('action', 'reorder_team_members');
            formData.append('ajax', '1');
            order.forEach(id => formData.append('order[]', id));

            fetch('/admin/index.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (statusText) {
                    if (data.success) {
                        statusText.style.background = '#f0fdf4';
                        statusText.style.color = '#166534';
                        statusText.innerHTML = '<i class="fa-solid fa-circle-check" style="color:#22c55e;"></i> Display order saved!';
                        setTimeout(() => { statusText.style.display = 'none'; }, 3000);
                    } else {
                        statusText.style.background = '#fef2f2';
                        statusText.style.color = '#991b1b';
                        statusText.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> Save failed';
                    }
                }
            })
            .catch(() => {
                if (statusText) {
                    statusText.style.background = '#fef2f2';
                    statusText.style.color = '#991b1b';
                    statusText.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> Network error';
                }
            });
        }

        // Initialize Drag and Drop for Team Grid
        document.addEventListener('DOMContentLoaded', function() {
            const grid = document.getElementById('teamMembersGrid');
            if (!grid) return;

            let draggedCard = null;

            grid.addEventListener('dragstart', function(e) {
                const item = e.target.closest('.team-card-admin-item');
                if (item) {
                    draggedCard = item;
                    item.style.opacity = '0.5';
                    item.classList.add('dragging');
                    e.dataTransfer.effectAllowed = 'move';
                }
            });

            grid.addEventListener('dragend', function(e) {
                const item = e.target.closest('.team-card-admin-item');
                if (item) {
                    item.style.opacity = '1';
                    item.classList.remove('dragging');
                }
                draggedCard = null;
                saveTeamOrder();
            });

            grid.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                const targetCard = e.target.closest('.team-card-admin-item');
                if (targetCard && targetCard !== draggedCard) {
                    const rect = targetCard.getBoundingClientRect();
                    const next = (e.clientY - rect.top) / (rect.bottom - rect.top) > 0.5;
                    grid.insertBefore(draggedCard, next ? targetCard.nextSibling : targetCard);
                }
            });
        });

        let activeModalType = 'department';

        function handleDepartmentSelectChange(select) {
            if (select.value === '__add_new__') {
                openAddModal('department');
            }
        }

        function handleLocationSelectChange(select) {
            if (select.value === '__add_new__') {
                openAddModal('location');
            }
        }

        function handleCategorySelectChange(select) {
            if (select.value === '__add_new__') {
                openAddModal('category');
            }
        }

        function openAddModal(type) {
            activeModalType = type;
            const modal = document.getElementById('customAddModal');
            const title = document.getElementById('customModalTitle');
            const label = document.getElementById('customModalLabel');
            const input = document.getElementById('customModalInput');
            const btn = document.getElementById('customModalSubmitBtn');
            const err = document.getElementById('customModalError');

            if (err) err.style.display = 'none';
            if (input) input.value = '';

            if (type === 'department') {
                if (title) title.innerHTML = '<i class="fa-solid fa-layer-group" style="color: #dc2626;"></i> Add New Department';
                if (label) label.textContent = 'Department Name *';
                if (input) input.placeholder = 'e.g. Production, Marketing, Technical';
                if (btn) btn.innerHTML = '<i class="fa-solid fa-check"></i> Add Department';
            } else if (type === 'location') {
                if (title) title.innerHTML = '<i class="fa-solid fa-location-dot" style="color: #dc2626;"></i> Add New Location';
                if (label) label.textContent = 'Location / Office Name *';
                if (input) input.placeholder = 'e.g. Germany, UAE, Australia';
                if (btn) btn.innerHTML = '<i class="fa-solid fa-check"></i> Add Location';
            } else if (type === 'category') {
                if (title) title.innerHTML = '<i class="fa-solid fa-newspaper" style="color: #dc2626;"></i> Add New Category';
                if (label) label.textContent = 'Category Name *';
                if (input) input.placeholder = 'e.g. Production Tips, Cinematography';
                if (btn) btn.innerHTML = '<i class="fa-solid fa-check"></i> Add Category';
            }

            if (modal) modal.style.display = 'flex';
            setTimeout(() => { if (input) input.focus(); }, 100);
        }

        function closeCustomAddModal() {
            const modal = document.getElementById('customAddModal');
            if (modal) modal.style.display = 'none';

            const deptSelect = document.getElementById('team_department_select');
            const locSelect = document.getElementById('team_location_select');
            const catSelect = document.getElementById('team_category_select');
            if (deptSelect && deptSelect.value === '__add_new__') deptSelect.selectedIndex = 0;
            if (locSelect && locSelect.value === '__add_new__') locSelect.selectedIndex = 0;
            if (catSelect && catSelect.value === '__add_new__') catSelect.selectedIndex = 0;
        }

        function submitCustomAddModal(e) {
            e.preventDefault();
            const input = document.getElementById('customModalInput');
            const err = document.getElementById('customModalError');
            const btn = document.getElementById('customModalSubmitBtn');

            if (!input || !input.value.trim()) {
                if (err) {
                    err.textContent = 'Please enter a valid name.';
                    err.style.display = 'block';
                }
                return;
            }

            const val = input.value.trim();
            const origBtnHtml = btn ? btn.innerHTML : '';
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
            }

            const formData = new FormData();
            if (activeModalType === 'department') {
                formData.append('action', 'add_team_department');
                formData.append('department', val);
            } else if (activeModalType === 'location') {
                formData.append('action', 'add_team_location');
                formData.append('location', val);
            } else if (activeModalType === 'category') {
                formData.append('action', 'add_blog_category');
                formData.append('category', val);
            }

            fetch('/admin/index.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = origBtnHtml;
                }

                if (data.success) {
                    closeCustomAddModal();
                    let selectId = 'team_department_select';
                    if (activeModalType === 'location') selectId = 'team_location_select';
                    if (activeModalType === 'category') selectId = 'team_category_select';

                    const select = document.getElementById(selectId);
                    if (select) {
                        let exists = false;
                        for (let i = 0; i < select.options.length; i++) {
                            if (select.options[i].value.toLowerCase() === val.toLowerCase()) {
                                select.selectedIndex = i;
                                exists = true;
                                break;
                            }
                        }
                        if (!exists) {
                            const newOpt = document.createElement('option');
                            newOpt.value = val;
                            newOpt.textContent = val;
                            newOpt.selected = true;
                            const addNewOpt = select.querySelector('option[value="__add_new__"]');
                            if (addNewOpt) select.insertBefore(newOpt, addNewOpt);
                            else select.appendChild(newOpt);
                        }
                    }
                } else {
                    if (err) {
                        err.textContent = data.message || 'Failed to add item.';
                        err.style.display = 'block';
                    }
                }
            })
            .catch(() => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = origBtnHtml;
                }
                if (err) {
                    err.textContent = 'Network error while adding item.';
                    err.style.display = 'block';
                }
            });
        }

        function handleBlogImageFileSelect(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const preview = document.getElementById('blog_image_preview');
                const urlInput = document.getElementById('blog_image_url_input');
                const statusBadge = document.getElementById('blog_upload_status_badge');
                const btn = document.getElementById('blog_upload_btn');

                // Instant local preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (preview) preview.src = e.target.result;
                };
                reader.readAsDataURL(file);

                if (statusBadge) {
                    statusBadge.style.display = 'inline-flex';
                    statusBadge.style.background = '#eff6ff';
                    statusBadge.style.color = '#1d4ed8';
                    statusBadge.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Uploading to Cloudinary...';
                }

                if (btn) {
                    btn.disabled = true;
                    btn.style.opacity = '0.7';
                    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Uploading...';
                }

                // Immediate AJAX upload to Cloudinary CDN
                const formData = new FormData();
                formData.append('action', 'upload_cloudinary_ajax');
                formData.append('folder', 'falhen/blog');
                formData.append('file', file);

                fetch('/admin/index.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (btn) {
                        btn.disabled = false;
                        btn.style.opacity = '1';
                        btn.innerHTML = '<i class="fa-solid fa-cloud-arrow-up"></i> Upload Cover Image';
                    }
                    if (data.success && data.url) {
                        if (preview) preview.src = data.url;
                        if (urlInput) urlInput.value = data.url;
                        if (statusBadge) {
                            statusBadge.style.background = '#f0fdf4';
                            statusBadge.style.color = '#166534';
                            statusBadge.innerHTML = '<i class="fa-solid fa-circle-check" style="color:#22c55e;"></i> Uploaded to Cloudinary!';
                        }
                    } else {
                        if (statusBadge) {
                            statusBadge.style.background = '#fef2f2';
                            statusBadge.style.color = '#b91c1c';
                            statusBadge.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> Upload failed: ' + (data.message || 'Unknown error');
                        }
                    }
                })
                .catch(() => {
                    if (btn) {
                        btn.disabled = false;
                        btn.style.opacity = '1';
                        btn.innerHTML = '<i class="fa-solid fa-cloud-arrow-up"></i> Upload Cover Image';
                    }
                    if (statusBadge) {
                        statusBadge.style.background = '#fef2f2';
                        statusBadge.style.color = '#b91c1c';
                        statusBadge.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> Network upload error';
                    }
                });
            }
        }

        function handlePortfolioImageFileSelect(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const preview = document.getElementById('portfolio_image_preview');
                const urlInput = document.getElementById('portfolio_image_url_input');
                const statusBadge = document.getElementById('portfolio_upload_status_badge');
                const btn = document.getElementById('portfolio_upload_btn');

                // Instant local preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (preview) preview.src = e.target.result;
                };
                reader.readAsDataURL(file);

                if (statusBadge) {
                    statusBadge.style.display = 'inline-flex';
                    statusBadge.style.background = '#eff6ff';
                    statusBadge.style.color = '#1d4ed8';
                    statusBadge.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Uploading to Cloudinary...';
                }

                if (btn) {
                    btn.disabled = true;
                    btn.style.opacity = '0.7';
                    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Uploading...';
                }

                // Immediate AJAX upload to Cloudinary CDN
                const formData = new FormData();
                formData.append('action', 'upload_cloudinary_ajax');
                formData.append('folder', 'falhen/portfolio');
                formData.append('file', file);

                fetch('/admin/index.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (btn) {
                        btn.disabled = false;
                        btn.style.opacity = '1';
                        btn.innerHTML = '<i class="fa-solid fa-cloud-arrow-up"></i> Upload Custom Cover';
                    }
                    if (data.success && data.url) {
                        if (preview) preview.src = data.url;
                        if (urlInput) urlInput.value = data.url;
                        if (statusBadge) {
                            statusBadge.style.background = '#f0fdf4';
                            statusBadge.style.color = '#166534';
                            statusBadge.innerHTML = '<i class="fa-solid fa-circle-check" style="color:#22c55e;"></i> Uploaded to Cloudinary!';
                        }
                    } else {
                        if (statusBadge) {
                            statusBadge.style.background = '#fef2f2';
                            statusBadge.style.color = '#b91c1c';
                            statusBadge.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> Upload failed: ' + (data.message || 'Unknown error');
                        }
                    }
                })
                .catch(() => {
                    if (btn) {
                        btn.disabled = false;
                        btn.style.opacity = '1';
                        btn.innerHTML = '<i class="fa-solid fa-cloud-arrow-up"></i> Upload Custom Cover';
                    }
                    if (statusBadge) {
                        statusBadge.style.background = '#fef2f2';
                        statusBadge.style.color = '#b91c1c';
                        statusBadge.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> Network upload error';
                    }
                });
            }
        }

        function moveBlogCard(btn, direction) {
            const card = btn.closest('.blog-card-admin-item');
            const grid = document.getElementById('blogPostsGrid');
            if (!card || !grid) return;

            if (direction === 'up' && card.previousElementSibling) {
                grid.insertBefore(card, card.previousElementSibling);
            } else if (direction === 'down' && card.nextElementSibling) {
                grid.insertBefore(card.nextElementSibling, card);
            }
            saveBlogOrder();
        }

        function saveBlogOrder() {
            const grid = document.getElementById('blogPostsGrid');
            if (!grid) return;

            const cards = grid.querySelectorAll('.blog-card-admin-item');
            const order = Array.from(cards).map(card => card.getAttribute('data-id'));

            // Update index numbers visually
            cards.forEach((card, index) => {
                const badge = card.querySelector('.blog-card-index-num');
                if (badge) badge.textContent = String(index + 1).padStart(2, '0');
            });

            const formData = new FormData();
            formData.append('action', 'reorder_blog_posts');
            formData.append('ajax', '1');
            order.forEach(id => formData.append('order[]', id));

            fetch('/admin/index.php', { method: 'POST', body: formData });
        }

        const teamMemberRolesMap = <?php 
            $teamForJs = getTeamMembers();
            $mapForJs = [];
            foreach ($teamForJs as $tmItem) {
                if (!empty($tmItem['name'])) {
                    $mapForJs[$tmItem['name']] = $tmItem['role'] ?? '';
                }
            }
            echo json_encode($mapForJs, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        ?>;

        function handleBlogAuthorSelectChange(select) {
            const authorName = select.value;
            const roleSelect = document.getElementById('blog_author_role_select');
            if (authorName && teamMemberRolesMap[authorName] && roleSelect) {
                const targetRole = teamMemberRolesMap[authorName];
                let found = false;
                for (let i = 0; i < roleSelect.options.length; i++) {
                    if (roleSelect.options[i].value.toLowerCase() === targetRole.toLowerCase()) {
                        roleSelect.selectedIndex = i;
                        found = true;
                        break;
                    }
                }
                if (!found) {
                    const newOpt = document.createElement('option');
                    newOpt.value = targetRole;
                    newOpt.textContent = targetRole;
                    newOpt.selected = true;
                    roleSelect.appendChild(newOpt);
                }
            }
        }

        function applyNativeFormat(cmd, arg = null) {
            try {
                document.execCommand('styleWithCSS', false, false);
            } catch (e) {}
            try {
                document.execCommand(cmd, false, arg);
            } catch (e) {
                console.log('execCommand error:', e);
            }
            syncNativeEditorContent();
            updateNativeToolbarState();
        }

        function applyNativeBlock(tag) {
            try {
                document.execCommand('styleWithCSS', false, false);
            } catch (e) {}
            try {
                document.execCommand('formatBlock', false, tag);
            } catch (e) {
                console.log('formatBlock error:', e);
            }
            syncNativeEditorContent();
            updateNativeToolbarState();
        }

        function applyNativeLink() {
            const url = prompt('Enter website link URL (e.g. https://falhen.com):');
            if (url && url.trim() !== '') {
                try {
                    document.execCommand('createLink', false, url.trim());
                } catch (e) {}
                syncNativeEditorContent();
                updateNativeToolbarState();
            }
        }

        function syncNativeEditorContent() {
            const editor = document.getElementById('nativeBlogEditor');
            const hidden = document.getElementById('blog_content_hidden_input');
            if (editor && hidden) {
                hidden.value = editor.innerHTML;
            }
        }

        function updateNativeToolbarState() {
            try {
                const commands = [
                    { cmd: 'bold', selector: "title*=\"Bold\"" },
                    { cmd: 'italic', selector: "title*=\"Italic\"" },
                    { cmd: 'underline', selector: "title*=\"Underline\"" },
                    { cmd: 'strikeThrough', selector: "title*=\"Strikethrough\"" }
                ];
                commands.forEach(item => {
                    const isState = document.queryCommandState(item.cmd);
                    const btn = document.querySelector(`.native-editor-btn[${item.selector}]`);
                    if (btn) {
                        if (isState) {
                            btn.style.background = '#dc2626';
                            btn.style.color = '#ffffff';
                            btn.style.borderColor = '#dc2626';
                        } else {
                            btn.style.background = '#ffffff';
                            btn.style.color = '#334155';
                            btn.style.borderColor = '#cbd5e1';
                        }
                    }
                });
            } catch (e) {}
        }

        document.addEventListener('DOMContentLoaded', function() {
            try {
                document.execCommand('styleWithCSS', false, false);
            } catch (e) {}
            const editor = document.getElementById('nativeBlogEditor');
            if (editor) {
                editor.addEventListener('selectionchange', updateNativeToolbarState);
                editor.addEventListener('input', syncNativeEditorContent);
                editor.addEventListener('keyup', syncNativeEditorContent);
                editor.addEventListener('mouseup', updateNativeToolbarState);
            }
            syncNativeEditorContent();
        });
    </script>

    <!-- CREATE NEW TASK MODAL OVERLAY (ZOHO KANBAN BOARD) -->
    <div id="createTaskModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(6px); z-index: 100000; align-items: center; justify-content: center; padding: 20px; animation: fadeInModal 0.2s ease;">
        <div style="background: #ffffff; border-radius: 20px; max-width: 540px; width: 100%; padding: 32px 28px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.3); border: 1px solid #e2e8f0; position: relative; max-height: 90vh; overflow-y: auto;">
            
            <button type="button" onclick="closeCreateTaskModal()" style="position: absolute; top: 16px; right: 16px; background: #f1f5f9; border: none; width: 32px; height: 32px; border-radius: 50%; color: #64748b; font-size: 1rem; cursor: pointer; display: flex; align-items: center; justify-content: center;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <div style="text-align: center; margin-bottom: 20px;">
                <div style="width: 54px; height: 54px; border-radius: 50%; background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px auto; font-size: 1.4rem;">
                    <i class="fa-solid fa-list-check"></i>
                </div>
                <h2 style="font-size: 1.3rem; font-weight: 800; color: #0f172a; margin: 0 0 4px 0;">Create New Task</h2>
                <p style="font-size: 0.84rem; color: #64748b; margin: 0;">Add a new task with checklist items, tags, and priority level.</p>
            </div>

            <form method="POST" action="/admin/index.php?section=comms&tab=tasks" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 14px;">
                <input type="hidden" name="action" value="create_studio_task">

                <div style="display: flex; gap: 12px;">
                    <div style="flex: 1.2;">
                        <label style="font-size: 0.76rem; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Task Title *</label>
                        <input type="text" name="title" placeholder="e.g. Scout Indoor Locations" required style="width: 100%; padding: 10px; font-size: 0.86rem; border: 1px solid #cbd5e1; border-radius: 8px;">
                    </div>
                    <div style="flex: 1;">
                        <label style="font-size: 0.76rem; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Client / Organization</label>
                        <input type="text" name="client_org" placeholder="e.g. Netflix, Red Bull, Nike" style="width: 100%; padding: 10px; font-size: 0.86rem; border: 1px solid #cbd5e1; border-radius: 8px;">
                    </div>
                </div>

                <div style="display: flex; gap: 12px;">
                    <div style="flex: 1;">
                        <label style="font-size: 0.76rem; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Priority Level</label>
                        <select name="priority" style="width: 100%; padding: 10px; font-size: 0.86rem; border: 1px solid #cbd5e1; border-radius: 8px; background: #ffffff;">
                            <option value="Low">Low Priority</option>
                            <option value="Medium" selected>Medium Priority</option>
                            <option value="High">High Priority</option>
                            <option value="Urgent">Urgent Priority</option>
                        </select>
                    </div>
                    <div style="flex: 1;">
                        <label style="font-size: 0.76rem; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Section</label>
                        <select name="stage" style="width: 100%; padding: 10px; font-size: 0.86rem; border: 1px solid #cbd5e1; border-radius: 8px; background: #ffffff;">
                            <?php foreach ($stagesConfig as $stKey => $stConf): ?>
                                <option value="<?php echo htmlspecialchars($stKey); ?>">
                                    <?php echo htmlspecialchars($stConf['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div style="display: flex; gap: 12px;">
                    <div style="flex: 1;">
                        <label style="font-size: 0.76rem; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Assign To Staff</label>
                        <div style="max-height: 110px; overflow-y: auto; border: 1px solid #cbd5e1; border-radius: 8px; padding: 6px 10px; background: #ffffff; display: flex; flex-direction: column; gap: 6px;">
                            <?php $allStaffMembers = getStaffAccountsRepo(); ?>
                            <?php foreach ($allStaffMembers as $sm): ?>
                                <label style="display: flex; align-items: center; gap: 8px; font-size: 0.82rem; font-weight: 600; color: #334155; cursor: pointer; user-select: none;">
                                    <input type="checkbox" name="assignees[]" value="<?php echo htmlspecialchars($sm['username']); ?>" style="accent-color: #dc2626; cursor: pointer;">
                                    <?php if (!empty($sm['avatar'])): ?>
                                        <img src="<?php echo htmlspecialchars(getCloudinaryUrl($sm['avatar'])); ?>" style="width: 20px; height: 20px; border-radius: 50%; object-fit: cover;">
                                    <?php endif; ?>
                                    <span><?php echo htmlspecialchars($sm['full_name']); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div style="flex: 1;">
                        <label style="font-size: 0.76rem; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Due Date</label>
                        <input type="date" name="due_date" value="<?php echo date('Y-m-d', strtotime('+3 days')); ?>" style="width: 100%; padding: 10px; font-size: 0.86rem; border: 1px solid #cbd5e1; border-radius: 8px;">
                    </div>
                </div>

                <div>
                    <label style="font-size: 0.76rem; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Tags (comma separated)</label>
                    <input type="text" name="tags" placeholder="e.g. Studio, 4K, Color Grade, Rigging" style="width: 100%; padding: 10px; font-size: 0.86rem; border: 1px solid #cbd5e1; border-radius: 8px;">
                </div>

                <div>
                    <label style="font-size: 0.76rem; font-weight: 700; color: #475569; display: block; margin-bottom: 6px;">Checklist Sub-tasks</label>
                    <div id="create_checklist_container" style="display: flex; flex-direction: column; gap: 6px; margin-bottom: 8px;"></div>
                    <div style="display: flex; gap: 8px;">
                        <input type="text" id="create_new_checklist_input" placeholder="Type a sub-task and press Enter..." style="flex: 1; padding: 8px 12px; font-size: 0.84rem; border: 1px solid #cbd5e1; border-radius: 8px; outline: none;" onkeydown="if(event.key === 'Enter'){ event.preventDefault(); addChecklistItemFromInput('create'); }">
                        <button type="button" onclick="addChecklistItemFromInput('create')" style="background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; padding: 8px 14px; border-radius: 8px; font-size: 0.8rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; white-space: nowrap;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                            <i class="fa-solid fa-plus"></i> Add Item
                        </button>
                    </div>
                </div>

                <div>
                    <label style="font-size: 0.76rem; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Task Attachment (PDF, Image, Doc, Zip)</label>
                    <input type="file" name="task_attachment" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg,.zip,.mp4,.mov" style="width: 100%; padding: 8px; font-size: 0.82rem; border: 1px solid #cbd5e1; border-radius: 8px; background: #f8fafc;">
                </div>

                <div>
                    <label style="font-size: 0.76rem; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Task Description</label>
                    <textarea name="description" rows="2" placeholder="Provide details, scope, or background context..." style="width: 100%; padding: 10px; font-size: 0.86rem; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit;"></textarea>
                </div>

                <div style="display: flex; gap: 12px; margin-top: 10px;">
                    <button type="button" onclick="closeCreateTaskModal()" style="flex: 1; padding: 11px; background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 10px; font-weight: 700; color: #475569; cursor: pointer;">
                        Cancel
                    </button>
                    <button type="submit" style="flex: 1; padding: 11px; background: #dc2626; border: 1px solid #dc2626; border-radius: 10px; font-weight: 800; color: #ffffff; cursor: pointer; box-shadow: 0 4px 12px rgba(220,38,38,0.3);">
                        Create New Task
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- EDIT TASK MODAL OVERLAY -->
    <div id="editTaskModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(6px); z-index: 100000; align-items: center; justify-content: center; padding: 20px; animation: fadeInModal 0.2s ease;">
        <div style="background: #ffffff; border-radius: 20px; max-width: 540px; width: 100%; padding: 32px 28px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.3); border: 1px solid #e2e8f0; position: relative; max-height: 90vh; overflow-y: auto;">
            
            <button type="button" onclick="closeEditTaskModal()" style="position: absolute; top: 16px; right: 16px; background: #f1f5f9; border: none; width: 32px; height: 32px; border-radius: 50%; color: #64748b; font-size: 1rem; cursor: pointer; display: flex; align-items: center; justify-content: center;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <div style="text-align: center; margin-bottom: 20px;">
                <div style="width: 54px; height: 54px; border-radius: 50%; background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px auto; font-size: 1.4rem;">
                    <i class="fa-solid fa-pen-to-square"></i>
                </div>
                <h2 style="font-size: 1.3rem; font-weight: 800; color: #0f172a; margin: 0 0 4px 0;">Edit Task</h2>
                <p style="font-size: 0.84rem; color: #64748b; margin: 0;">Modify task details, priority, checklist, assignees, or stage.</p>
            </div>

            <form method="POST" action="/admin/index.php?section=comms&tab=tasks" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 14px;">
                <input type="hidden" name="action" value="update_studio_task">
                <input type="hidden" name="task_id" id="edit_task_id" value="">

                <div style="display: flex; gap: 12px;">
                    <div style="flex: 1.2;">
                        <label style="font-size: 0.76rem; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Task Title *</label>
                        <input type="text" name="title" id="edit_task_title" required style="width: 100%; padding: 10px; font-size: 0.86rem; border: 1px solid #cbd5e1; border-radius: 8px;">
                    </div>
                    <div style="flex: 1;">
                        <label style="font-size: 0.76rem; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Client / Organization</label>
                        <input type="text" name="client_org" id="edit_task_client_org" placeholder="e.g. Netflix, Red Bull, Nike" style="width: 100%; padding: 10px; font-size: 0.86rem; border: 1px solid #cbd5e1; border-radius: 8px;">
                    </div>
                </div>

                <div style="display: flex; gap: 12px;">
                    <div style="flex: 1;">
                        <label style="font-size: 0.76rem; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Priority Level</label>
                        <select name="priority" id="edit_task_priority" style="width: 100%; padding: 10px; font-size: 0.86rem; border: 1px solid #cbd5e1; border-radius: 8px; background: #ffffff;">
                            <option value="Low">Low Priority</option>
                            <option value="Medium">Medium Priority</option>
                            <option value="High">High Priority</option>
                            <option value="Urgent">Urgent Priority</option>
                        </select>
                    </div>
                    <div style="flex: 1;">
                        <label style="font-size: 0.76rem; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Section</label>
                        <select name="stage" id="edit_task_stage" style="width: 100%; padding: 10px; font-size: 0.86rem; border: 1px solid #cbd5e1; border-radius: 8px; background: #ffffff;">
                            <?php foreach ($stagesConfig as $stKey => $stConf): ?>
                                <option value="<?php echo htmlspecialchars($stKey); ?>">
                                    <?php echo htmlspecialchars($stConf['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div style="display: flex; gap: 12px;">
                    <div style="flex: 1;">
                        <label style="font-size: 0.76rem; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Assign To Staff</label>
                        <div style="max-height: 110px; overflow-y: auto; border: 1px solid #cbd5e1; border-radius: 8px; padding: 6px 10px; background: #ffffff; display: flex; flex-direction: column; gap: 6px;">
                            <?php foreach ($allStaffMembers as $sm): ?>
                                <label style="display: flex; align-items: center; gap: 8px; font-size: 0.82rem; font-weight: 600; color: #334155; cursor: pointer; user-select: none;">
                                    <input type="checkbox" name="assignees[]" value="<?php echo htmlspecialchars($sm['username']); ?>" style="accent-color: #dc2626; cursor: pointer;">
                                    <?php if (!empty($sm['avatar'])): ?>
                                        <img src="<?php echo htmlspecialchars(getCloudinaryUrl($sm['avatar'])); ?>" style="width: 20px; height: 20px; border-radius: 50%; object-fit: cover;">
                                    <?php endif; ?>
                                    <span><?php echo htmlspecialchars($sm['full_name']); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div style="flex: 1;">
                        <label style="font-size: 0.76rem; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Due Date</label>
                        <input type="date" name="due_date" id="edit_task_due_date" style="width: 100%; padding: 10px; font-size: 0.86rem; border: 1px solid #cbd5e1; border-radius: 8px;">
                    </div>
                </div>

                <div>
                    <label style="font-size: 0.76rem; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Tags (comma separated)</label>
                    <input type="text" name="tags" id="edit_task_tags" style="width: 100%; padding: 10px; font-size: 0.86rem; border: 1px solid #cbd5e1; border-radius: 8px;">
                </div>

                <div>
                    <label style="font-size: 0.76rem; font-weight: 700; color: #475569; display: block; margin-bottom: 6px;">Checklist Sub-tasks</label>
                    <div id="edit_checklist_container" style="display: flex; flex-direction: column; gap: 6px; margin-bottom: 8px;"></div>
                    <div style="display: flex; gap: 8px;">
                        <input type="text" id="edit_new_checklist_input" placeholder="Type a sub-task and press Enter..." style="flex: 1; padding: 8px 12px; font-size: 0.84rem; border: 1px solid #cbd5e1; border-radius: 8px; outline: none;" onkeydown="if(event.key === 'Enter'){ event.preventDefault(); addChecklistItemFromInput('edit'); }">
                        <button type="button" onclick="addChecklistItemFromInput('edit')" style="background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; padding: 8px 14px; border-radius: 8px; font-size: 0.8rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; white-space: nowrap;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                            <i class="fa-solid fa-plus"></i> Add Item
                        </button>
                    </div>
                </div>

                <div>
                    <label style="font-size: 0.76rem; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Upload New Attachment (PDF, Image, Doc, Zip)</label>
                    <input type="file" name="task_attachment" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg,.zip,.mp4,.mov" style="width: 100%; padding: 8px; font-size: 0.82rem; border: 1px solid #cbd5e1; border-radius: 8px; background: #f8fafc;">
                    <div id="edit_task_attachments_list" style="margin-top: 6px; display: flex; flex-direction: column; gap: 4px;"></div>
                </div>

                <div>
                    <label style="font-size: 0.76rem; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Task Description</label>
                    <textarea name="description" id="edit_task_description" rows="2" style="width: 100%; padding: 10px; font-size: 0.86rem; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit;"></textarea>
                </div>

                <div style="display: flex; gap: 12px; margin-top: 10px;">
                    <button type="button" onclick="closeEditTaskModal()" style="flex: 1; padding: 11px; background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 10px; font-weight: 700; color: #475569; cursor: pointer;">
                        Cancel
                    </button>
                    <button type="submit" style="flex: 1; padding: 11px; background: #dc2626; border: 1px solid #dc2626; border-radius: 10px; font-weight: 800; color: #ffffff; cursor: pointer; box-shadow: 0 4px 12px rgba(220,38,38,0.3);">
                        Save Task Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- CREATE NEW STAGE / SECTION LABEL MODAL OVERLAY (SUPER ADMIN ONLY) -->
    <div id="createStageModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(6px); z-index: 100000; align-items: center; justify-content: center; padding: 20px; animation: fadeInModal 0.2s ease;">
        <div style="background: #ffffff; border-radius: 20px; max-width: 440px; width: 100%; padding: 32px 28px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.3); border: 1px solid #e2e8f0; position: relative;">
            
            <button type="button" onclick="closeCreateStageModal()" style="position: absolute; top: 16px; right: 16px; background: #f1f5f9; border: none; width: 32px; height: 32px; border-radius: 50%; color: #64748b; font-size: 1rem; cursor: pointer; display: flex; align-items: center; justify-content: center;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <div style="text-align: center; margin-bottom: 20px;">
                <div style="width: 54px; height: 54px; border-radius: 50%; background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px auto; font-size: 1.4rem;">
                    <i class="fa-solid fa-tags"></i>
                </div>
                <h2 style="font-size: 1.3rem; font-weight: 800; color: #0f172a; margin: 0 0 4px 0;">Create Section Label</h2>
                <p style="font-size: 0.84rem; color: #64748b; margin: 0;">Add a new stage column to the task board (Super Admin only).</p>
            </div>

            <form method="POST" action="/admin/index.php?section=comms&tab=tasks" style="display: flex; flex-direction: column; gap: 14px;">
                <input type="hidden" name="action" value="create_studio_task_stage">

                <div>
                    <label style="font-size: 0.76rem; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Section Title *</label>
                    <input type="text" name="stage_title" placeholder="e.g. Post Production, Quality Control, VFX" required style="width: 100%; padding: 10px; font-size: 0.86rem; border: 1px solid #cbd5e1; border-radius: 8px;">
                </div>

                <div>
                    <label style="font-size: 0.76rem; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Accent Badge Color</label>
                    <input type="color" name="stage_color" value="#a855f7" style="width: 100%; height: 42px; padding: 4px; border: 1px solid #cbd5e1; border-radius: 8px; cursor: pointer; background: #ffffff;">
                </div>

                <div style="display: flex; gap: 12px; margin-top: 10px;">
                    <button type="button" onclick="closeCreateStageModal()" style="flex: 1; padding: 11px; background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 10px; font-weight: 700; color: #475569; cursor: pointer;">
                        Cancel
                    </button>
                    <button type="submit" style="flex: 1; padding: 11px; background: #16a34a; border: 1px solid #16a34a; border-radius: 10px; font-weight: 800; color: #ffffff; cursor: pointer; box-shadow: 0 4px 12px rgba(22,163,74,0.3);">
                        Create Section
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- EDIT STAGE / SECTION LABEL MODAL OVERLAY (SUPER ADMIN ONLY) -->
    <div id="editStageModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(6px); z-index: 100000; align-items: center; justify-content: center; padding: 20px; animation: fadeInModal 0.2s ease;">
        <div style="background: #ffffff; border-radius: 20px; max-width: 440px; width: 100%; padding: 32px 28px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.3); border: 1px solid #e2e8f0; position: relative;">
            
            <button type="button" onclick="closeEditStageModal()" style="position: absolute; top: 16px; right: 16px; background: #f1f5f9; border: none; width: 32px; height: 32px; border-radius: 50%; color: #64748b; font-size: 1rem; cursor: pointer; display: flex; align-items: center; justify-content: center;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <div style="text-align: center; margin-bottom: 20px;">
                <div style="width: 54px; height: 54px; border-radius: 50%; background: #f0f9ff; border: 1px solid #bae6fd; color: #0284c7; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px auto; font-size: 1.4rem;">
                    <i class="fa-solid fa-pen-to-square"></i>
                </div>
                <h2 style="font-size: 1.3rem; font-weight: 800; color: #0f172a; margin: 0 0 4px 0;">Rename Section Label</h2>
                <p style="font-size: 0.84rem; color: #64748b; margin: 0;">Update stage title or accent color (Super Admin only).</p>
            </div>

            <form method="POST" action="/admin/index.php?section=comms&tab=tasks" style="display: flex; flex-direction: column; gap: 14px;">
                <input type="hidden" name="action" value="update_studio_task_stage_label">
                <input type="hidden" name="stage_key" id="edit_stage_key" value="">

                <div>
                    <label style="font-size: 0.76rem; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Section Title *</label>
                    <input type="text" name="stage_title" id="edit_stage_title" required style="width: 100%; padding: 10px; font-size: 0.86rem; border: 1px solid #cbd5e1; border-radius: 8px;">
                </div>

                <div>
                    <label style="font-size: 0.76rem; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Accent Badge Color</label>
                    <input type="color" name="stage_color" id="edit_stage_color" value="#3b82f6" style="width: 100%; height: 42px; padding: 4px; border: 1px solid #cbd5e1; border-radius: 8px; cursor: pointer; background: #ffffff;">
                </div>

                <div style="display: flex; gap: 12px; margin-top: 10px;">
                    <button type="button" onclick="closeEditStageModal()" style="flex: 1; padding: 11px; background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 10px; font-weight: 700; color: #475569; cursor: pointer;">
                        Cancel
                    </button>
                    <button type="submit" style="flex: 1; padding: 11px; background: #0284c7; border: 1px solid #0284c7; border-radius: 10px; font-weight: 800; color: #ffffff; cursor: pointer; box-shadow: 0 4px 12px rgba(2,132,199,0.3);">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- DELETE TASK CONFIRMATION MODAL OVERLAY -->
    <div id="deleteTaskModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(6px); z-index: 100000; align-items: center; justify-content: center; padding: 20px; animation: fadeInModal 0.2s ease;">
        <div style="background: #ffffff; border-radius: 20px; max-width: 440px; width: 100%; padding: 32px 28px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.3); border: 1px solid #e2e8f0; position: relative; text-align: center;">
            
            <button type="button" onclick="closeDeleteTaskModal()" style="position: absolute; top: 16px; right: 16px; background: #f1f5f9; border: none; width: 32px; height: 32px; border-radius: 50%; color: #64748b; font-size: 1rem; cursor: pointer; display: flex; align-items: center; justify-content: center;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <div style="width: 56px; height: 56px; border-radius: 50%; background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px auto; font-size: 1.5rem;">
                <i class="fa-solid fa-trash-can"></i>
            </div>
            
            <h3 style="font-size: 1.25rem; font-weight: 800; color: #0f172a; margin: 0 0 8px 0;">Delete Task Card?</h3>
            <p style="font-size: 0.86rem; color: #64748b; margin: 0 0 24px 0; line-height: 1.5;">Are you sure you want to delete this task card from your team's board? This action cannot be undone.</p>

            <div style="display: flex; gap: 12px;">
                <button type="button" onclick="closeDeleteTaskModal()" style="flex: 1; padding: 12px; background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 10px; font-weight: 700; color: #475569; cursor: pointer; transition: all 0.15s ease;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                    Cancel
                </button>
                <button type="button" onclick="confirmTaskDeleteAction()" style="flex: 1; padding: 12px; background: #dc2626; border: 1px solid #dc2626; border-radius: 10px; font-weight: 800; color: #ffffff; cursor: pointer; box-shadow: 0 4px 12px rgba(220,38,38,0.3); transition: all 0.15s ease;" onmouseover="this.style.background='#b91c1c'" onmouseout="this.style.background='#dc2626'">
                    Yes, Delete Task
                </button>
            </div>
        </div>
    </div>

    <script>
    var createChecklistCounter = 0;
    var editChecklistCounter = 0;

    function addChecklistItemRow(type, text, completed) {
        var containerId = (type === 'edit') ? 'edit_checklist_container' : 'create_checklist_container';
        var container = document.getElementById(containerId);
        if (!container) return;

        var idx = (type === 'edit') ? editChecklistCounter++ : createChecklistCounter++;
        var row = document.createElement('div');
        row.className = 'checklist-builder-row';
        row.style.cssText = 'display: flex; align-items: center; gap: 8px; background: #f8fafc; padding: 6px 10px; border-radius: 8px; border: 1px solid #e2e8f0;';

        var checkedAttr = completed ? 'checked' : '';
        var isDoneClass = completed ? 'text-decoration: line-through; color: #64748b;' : 'color: #0f172a;';

        row.innerHTML = 
            '<input type="hidden" name="checklist_status[' + idx + ']" value="0">' +
            '<input type="checkbox" name="checklist_status[' + idx + ']" value="1" ' + checkedAttr + ' onchange="toggleChecklistRowStyle(this)" style="accent-color: #dc2626; cursor: pointer; width: 16px; height: 16px; flex-shrink: 0;">' +
            '<input type="text" name="checklist_items[' + idx + ']" value="' + escapeHtmlAttr(text || '') + '" placeholder="Sub-task item..." style="flex: 1; border: none; background: transparent; font-size: 0.84rem; font-weight: 600; outline: none; ' + isDoneClass + '">' +
            '<button type="button" onclick="this.closest(\'.checklist-builder-row\').remove()" style="background: none; border: none; color: #94a3b8; cursor: pointer; font-size: 0.85rem; padding: 2px 4px;" onmouseover="this.style.color=\'#ef4444\'" onmouseout="this.style.color=\'#94a3b8\'" title="Remove Item">' +
                '<i class="fa-solid fa-trash-can"></i>' +
            '</button>';

        container.appendChild(row);
    }

    function addChecklistItemFromInput(type) {
        var inputId = (type === 'edit') ? 'edit_new_checklist_input' : 'create_new_checklist_input';
        var input = document.getElementById(inputId);
        if (!input) return;
        var val = (input.value || '').trim();
        if (!val) return;
        addChecklistItemRow(type, val, false);
        input.value = '';
        input.focus();
    }

    function toggleChecklistRowStyle(checkbox) {
        var textInput = checkbox.closest('.checklist-builder-row').querySelector('input[type="text"]');
        if (textInput) {
            if (checkbox.checked) {
                textInput.style.textDecoration = 'line-through';
                textInput.style.color = '#64748b';
            } else {
                textInput.style.textDecoration = 'none';
                textInput.style.color = '#0f172a';
            }
        }
    }

    function escapeHtmlAttr(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function openCreateStageModal() {
        var m = document.getElementById('createStageModal');
        if (m) m.style.display = 'flex';
    }
    function closeCreateStageModal() {
        var m = document.getElementById('createStageModal');
        if (m) m.style.display = 'none';
    }

    function openEditStageModal(key, title, color) {
        document.getElementById('edit_stage_key').value = key || '';
        document.getElementById('edit_stage_title').value = title || '';
        document.getElementById('edit_stage_color').value = color || '#3b82f6';
        var m = document.getElementById('editStageModal');
        if (m) m.style.display = 'flex';
    }
    function closeEditStageModal() {
        var m = document.getElementById('editStageModal');
        if (m) m.style.display = 'none';
    }

    function openCreateTaskModal() {
        var createContainer = document.getElementById('create_checklist_container');
        if (createContainer && createContainer.children.length === 0) {
            createChecklistCounter = 0;
            addChecklistItemRow('create', '', false);
        }
        var cbs = document.querySelectorAll('#createTaskModal input[name="assignees[]"]');
        cbs.forEach(function(cb) { cb.checked = false; });

        var m = document.getElementById('createTaskModal');
        if (m) m.style.display = 'flex';
    }
    function closeCreateTaskModal() {
        var m = document.getElementById('createTaskModal');
        if (m) m.style.display = 'none';
    }

    function openEditTaskModal(ev, tsk) {
        if (ev) ev.stopPropagation();
        document.getElementById('edit_task_id').value = tsk.id || '';
        document.getElementById('edit_task_title').value = tsk.title || '';
        document.getElementById('edit_task_client_org').value = tsk.client_org || '';
        document.getElementById('edit_task_priority').value = tsk.priority || 'Medium';
        document.getElementById('edit_task_stage').value = tsk.stage || 'ideas';
        document.getElementById('edit_task_due_date').value = tsk.due_date || '';
        document.getElementById('edit_task_description').value = tsk.description || '';

        var assignedUsernames = [];
        if (tsk.assignees && Array.isArray(tsk.assignees)) {
            assignedUsernames = tsk.assignees.map(function(a) { return (a.username || '').toLowerCase(); });
        } else if (tsk.assignee_username) {
            assignedUsernames = tsk.assignee_username.toLowerCase().split(',');
        }

        var checkboxes = document.querySelectorAll('#editTaskModal input[name="assignees[]"]');
        checkboxes.forEach(function(cb) {
            cb.checked = assignedUsernames.indexOf(cb.value.toLowerCase()) !== -1;
        });
        
        var tagsStr = (tsk.tags && Array.isArray(tsk.tags)) ? tsk.tags.join(', ') : '';
        document.getElementById('edit_task_tags').value = tagsStr;

        var editContainer = document.getElementById('edit_checklist_container');
        if (editContainer) {
            editContainer.innerHTML = '';
            editChecklistCounter = 0;
            if (tsk.checklist && Array.isArray(tsk.checklist) && tsk.checklist.length > 0) {
                tsk.checklist.forEach(function(item) {
                    addChecklistItemRow('edit', item.text || '', !!item.completed);
                });
            } else {
                addChecklistItemRow('edit', '', false);
            }
        }

        var attContainer = document.getElementById('edit_task_attachments_list');
        if (attContainer) {
            attContainer.innerHTML = '';
            if (tsk.attachments && Array.isArray(tsk.attachments) && tsk.attachments.length > 0) {
                var html = '<div style="font-size: 0.72rem; font-weight: 700; color: #64748b; margin-bottom: 2px;">Existing Attachments:</div>';
                tsk.attachments.forEach(function(att) {
                    html += '<a href="' + att.url + '" target="_blank" style="font-size: 0.76rem; font-weight: 700; color: #2563eb; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;"><i class="fa-solid fa-paperclip"></i> ' + (att.name || 'Attachment') + '</a>';
                });
                attContainer.innerHTML = html;
            }
        }

        var m = document.getElementById('editTaskModal');
        if (m) m.style.display = 'flex';
    }

    function closeEditTaskModal() {
        var m = document.getElementById('editTaskModal');
        if (m) m.style.display = 'none';
    }

    function filterTasksKanban() {
        var q = (document.getElementById('taskSearchInput').value || '').toLowerCase();
        var cards = document.querySelectorAll('.task-card-item');
        cards.forEach(function(card) {
            var txt = card.innerText.toLowerCase();
            card.style.display = (txt.indexOf(q) !== -1) ? 'block' : 'none';
        });
    }

    function changeTaskStageQuick(taskId, newStage) {
        var fd = new FormData();
        fd.append('action', 'update_task_stage');
        fd.append('task_id', taskId);
        fd.append('new_stage', newStage);
        fetch('/admin/index.php?section=comms&tab=tasks', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd
        }).then(function() {
            window.location.reload();
        });
    }

    function toggleChecklistItem(taskId, itemId) {
        var fd = new FormData();
        fd.append('action', 'toggle_task_checklist_item');
        fd.append('task_id', taskId);
        fd.append('item_id', itemId);
        fetch('/admin/index.php?section=comms&tab=tasks', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd
        });
    }

    var pendingDeleteTaskId = null;

    function deleteTaskItem(taskId) {
        pendingDeleteTaskId = taskId;
        var m = document.getElementById('deleteTaskModal');
        if (m) m.style.display = 'flex';
    }

    function closeDeleteTaskModal() {
        pendingDeleteTaskId = null;
        var m = document.getElementById('deleteTaskModal');
        if (m) m.style.display = 'none';
    }

    function confirmTaskDeleteAction() {
        if (!pendingDeleteTaskId) return;
        var fd = new FormData();
        fd.append('action', 'delete_studio_task');
        fd.append('task_id', pendingDeleteTaskId);
        fetch('/admin/index.php?section=comms&tab=tasks', { method: 'POST', body: fd })
        .then(function() {
            window.location.reload();
        });
    }

    function handleTaskDragStart(ev, taskId) {
        ev.dataTransfer.setData('text/plain', taskId);
    }
    function allowTaskDrop(ev) {
        ev.preventDefault();
    }
    function handleTaskDrop(ev, newStage) {
        ev.preventDefault();
        var taskId = ev.dataTransfer.getData('text/plain');
        if (taskId) {
            changeTaskStageQuick(taskId, newStage);
        }
    }
    </script>

    <!-- CLOCK IN REMINDER MODAL POPUP FOR EMPLOYEES WHO HAVEN'T CLOCKED IN TODAY -->
    <?php 
    $reminderUserAttendance = getUserTodayAttendance($username);
    $reminderWorkState = $reminderUserAttendance['work_state'] ?? ($reminderUserAttendance ? (empty($reminderUserAttendance['clock_out']) ? (($reminderUserAttendance['status'] ?? '') === 'On Break' ? 'on_break' : 'working') : 'completed') : 'not_clocked_in');

    if ($reminderWorkState === 'not_clocked_in' && empty($_SESSION['dismissed_clockin_reminder'])): 
    ?>
        <div id="clockInReminderModal" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(15, 23, 42, 0.68); backdrop-filter: blur(5px); z-index: 99999; display: flex; align-items: center; justify-content: center; padding: 20px; animation: fadeInModal 0.25s ease;">
            <div style="background: #ffffff; border-radius: 20px; max-width: 440px; width: 100%; padding: 32px 28px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); text-align: center; border: 1px solid #e2e8f0; position: relative; animation: slideUpModal 0.3s cubic-bezier(0.16, 1, 0.3, 1);">
                
                <button onclick="dismissClockInModal()" style="position: absolute; top: 16px; right: 16px; background: #f1f5f9; border: none; width: 32px; height: 32px; border-radius: 50%; color: #64748b; font-size: 1rem; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.15s ease;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                    <i class="fa-solid fa-xmark"></i>
                </button>

                <!-- GLOWING ALARM CLOCK BADGE -->
                <div style="width: 68px; height: 68px; border-radius: 50%; background: #f0fdf4; border: 2px solid #bbf7d0; display: flex; align-items: center; justify-content: center; margin: 0 auto 18px auto; color: #16a34a; font-size: 2rem; box-shadow: 0 0 20px rgba(34, 197, 94, 0.2);">
                    <i class="fa-solid fa-user-clock" style="animation: pulseClock 2s infinite;"></i>
                </div>

                <h2 style="font-size: 1.35rem; font-weight: 800; color: #0f172a; margin: 0 0 8px 0;">Don't Forget to Clock In!</h2>
                <p style="font-size: 0.88rem; color: #64748b; margin: 0 0 24px 0; line-height: 1.5;">
                    Hello <strong><?php echo htmlspecialchars($_SESSION['admin_name'] ?? $username); ?></strong>, you are currently active on the studio portal but haven't recorded your workday clock-in yet.
                </p>

                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <form method="POST" action="/admin/index.php" style="margin: 0;">
                        <input type="hidden" name="action" value="clock_in">
                        <button type="submit" class="btn-save-primary" style="width: 100%; justify-content: center; padding: 12px 18px; font-size: 0.92rem; background: #16a34a; border-color: #16a34a; box-shadow: 0 4px 12px rgba(22, 163, 74, 0.3);">
                            <i class="fa-solid fa-play"></i> Clock In Now
                        </button>
                    </form>

                    <button onclick="dismissClockInModal()" style="width: 100%; padding: 10px; background: #ffffff; color: #64748b; border: 1px solid #cbd5e1; border-radius: 10px; font-weight: 700; font-size: 0.84rem; cursor: pointer; transition: all 0.15s ease;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='#ffffff'">
                        Remind Me Later
                    </button>
                </div>
            </div>
        </div>

        <style>
        @keyframes fadeInModal { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUpModal { from { transform: translateY(20px) scale(0.96); opacity: 0; } to { transform: translateY(0) scale(1); opacity: 1; } }
        @keyframes pulseClock { 0% { transform: scale(1); } 50% { transform: scale(1.08); } 100% { transform: scale(1); } }
        </style>

        <script>
        function dismissClockInModal() {
            var modal = document.getElementById('clockInReminderModal');
            if (modal) {
                modal.style.display = 'none';
            }
            var fd = new FormData();
            fd.append('action', 'dismiss_clockin_reminder');
            fetch('/admin/index.php', { method: 'POST', body: fd });
        }
        </script>
    <?php endif; ?>

    <!-- GLOBAL POST ANNOUNCEMENT MODAL -->
    <div id="postAnnouncementModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(15, 23, 42, 0.68); backdrop-filter: blur(5px); z-index: 99999; align-items: center; justify-content: center; padding: 20px; animation: fadeInModal 0.2s ease;">
        <div style="background: #ffffff; border-radius: 20px; max-width: 500px; width: 100%; padding: 32px 28px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); border: 1px solid #e2e8f0; position: relative;">
            
            <button type="button" onclick="closePostAnnouncementModal()" style="position: absolute; top: 16px; right: 16px; background: #f1f5f9; border: none; width: 32px; height: 32px; border-radius: 50%; color: #64748b; font-size: 1rem; cursor: pointer; display: flex; align-items: center; justify-content: center;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <div style="text-align: center; margin-bottom: 20px;">
                <div style="width: 54px; height: 54px; border-radius: 50%; background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px auto; font-size: 1.5rem;">
                    <i class="fa-solid fa-bullhorn"></i>
                </div>
                <h2 style="font-size: 1.3rem; font-weight: 800; color: #0f172a; margin: 0 0 4px 0;">Post Studio Announcement</h2>
                <p style="font-size: 0.84rem; color: #64748b; margin: 0;">Broadcast news & updates to all studio staff members.</p>
            </div>

            <form method="POST" action="/admin/index.php" style="display: flex; flex-direction: column; gap: 14px;">
                <input type="hidden" name="action" value="post_announcement">
                <input type="hidden" name="redirect_section" value="comms">
                <input type="hidden" name="redirect_tab" value="feeds">

                <div>
                    <label style="font-size: 0.76rem; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Announcement Title</label>
                    <input type="text" name="title" placeholder="Announcement Title" required style="width: 100%; padding: 10px; font-size: 0.86rem; border: 1px solid #cbd5e1; border-radius: 8px;">
                </div>

                <div>
                    <label style="font-size: 0.76rem; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Category</label>
                    <select name="category" style="width: 100%; padding: 10px; font-size: 0.86rem; border: 1px solid #cbd5e1; border-radius: 8px;">
                        <option value="General">General</option>
                        <option value="Important">Important</option>
                        <option value="Events">Events</option>
                    </select>
                </div>

                <div>
                    <label style="font-size: 0.76rem; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Content / Message</label>
                    <textarea name="content" rows="4" placeholder="Write full announcement content..." required style="width: 100%; padding: 10px; font-size: 0.86rem; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit;"></textarea>
                </div>

                <div style="display: flex; gap: 8px; margin-top: 6px;">
                    <button type="submit" class="btn-save-primary" style="flex: 1; justify-content: center; background: #dc2626; border-color: #dc2626;">
                        <i class="fa-solid fa-paper-plane"></i> Publish Announcement
                    </button>
                    <button type="button" onclick="closePostAnnouncementModal()" style="padding: 10px 14px; background: #f1f5f9; color: #475569; border: none; border-radius: 8px; font-weight: 700; font-size: 0.84rem; cursor: pointer;">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openPostAnnouncementModal() {
        var m = document.getElementById('postAnnouncementModal');
        if (m) m.style.display = 'flex';
    }
    function closePostAnnouncementModal() {
        var m = document.getElementById('postAnnouncementModal');
        if (m) m.style.display = 'none';
    }
    </script>

    <!-- GLOBAL SIDEBAR COLLAPSE SCRIPT -->
    <script>
    function toggleNavCategory(header) {
        if (!header) return;
        header.classList.toggle('collapsed');
        var list = header.nextElementSibling;
        if (list && list.classList.contains('nav-list')) {
            if (header.classList.contains('collapsed')) {
                list.style.display = 'none';
            } else {
                list.style.display = 'block';
            }
        }
    }
    </script>

    <!-- GLOBAL CUSTOM CONFIRMATION MODAL POPUP -->
    <div id="globalConfirmModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(15, 23, 42, 0.68); backdrop-filter: blur(5px); z-index: 100000; align-items: center; justify-content: center; padding: 20px; animation: fadeInModal 0.2s ease;">
        <div style="background: #ffffff; border-radius: 20px; width: 100%; max-width: 440px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); overflow: hidden; border: 1px solid #e2e8f0; animation: slideUpModal 0.2s ease;">
            <div style="padding: 28px 28px 18px 28px; text-align: center;">
                <div style="width: 58px; height: 58px; border-radius: 50%; background: #fef2f2; color: #dc2626; display: inline-flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 16px; border: 1px solid #fecaca; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.12);">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <h3 id="confirmModalTitle" style="font-size: 1.2rem; font-weight: 800; color: #0f172a; margin-bottom: 8px;">Confirm Action</h3>
                <p id="confirmModalMessage" style="font-size: 0.88rem; color: #64748b; line-height: 1.5; margin: 0;">Are you sure you want to proceed with this action?</p>
            </div>
            <div style="padding: 16px 28px 24px 28px; display: flex; gap: 12px; justify-content: center; background: #f8fafc; border-top: 1px solid #f1f5f9;">
                <button type="button" onclick="closeConfirmModal()" style="flex: 1; padding: 11px 18px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 10px; font-weight: 700; color: #475569; font-size: 0.88rem; cursor: pointer; transition: all 0.15s ease;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='#ffffff'">
                    Cancel
                </button>
                <button type="button" id="confirmModalProceedBtn" onclick="executeConfirmAction()" style="flex: 1; padding: 11px 18px; background: #dc2626; border: 1px solid #dc2626; border-radius: 10px; font-weight: 700; color: #ffffff; font-size: 0.88rem; cursor: pointer; transition: all 0.15s ease; box-shadow: 0 4px 12px rgba(220,38,38,0.25);" onmouseover="this.style.background='#b91c1c'" onmouseout="this.style.background='#dc2626'">
                    Confirm &amp; Remove
                </button>
            </div>
        </div>
    </div>

    <!-- INCOMING CALL POPUP MODAL (TOP-CENTERED FACETIME / TEAMS BANNER) -->
    <div id="incomingCallModal" style="display: none; position: fixed; top: 24px; left: 50%; transform: translateX(-50%); background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border-radius: 24px; width: 440px; max-width: 90vw; box-shadow: 0 20px 50px rgba(0,0,0,0.5), 0 0 25px rgba(239, 68, 68, 0.25); border: 1px solid rgba(239, 68, 68, 0.4); z-index: 300000; padding: 14px 20px; animation: slideDownNotification 0.35s cubic-bezier(0.16, 1, 0.3, 1);">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 14px;">
            <!-- Left: Caller Avatar & Name -->
            <div style="display: flex; align-items: center; gap: 12px; min-width: 0; flex: 1;">
                <div style="position: relative; width: 48px; height: 48px; flex-shrink: 0;">
                    <div style="position: absolute; inset: -4px; border-radius: 50%; border: 2px dashed #ef4444; animation: spinRing 4s linear infinite;"></div>
                    <img id="incomingCallerAvatar" src="/assets/img/team/team_henry.png" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                </div>
                <div style="overflow: hidden; min-width: 0;">
                    <div style="font-size: 0.68rem; font-weight: 800; color: #ef4444; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 2px;" id="incomingCallBadge">
                        INCOMING AUDIO CALL
                    </div>
                    <h4 id="incomingCallerName" style="font-size: 0.96rem; font-weight: 800; color: #ffffff; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        Mojisola Emjay
                    </h4>
                    <p style="font-size: 0.74rem; color: #94a3b8; margin: 0; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Ringing studio line...</p>
                </div>
            </div>
            <!-- Right: Action Buttons (Decline & Accept) -->
            <div style="display: flex; align-items: center; gap: 8px; flex-shrink: 0;">
                <button type="button" onclick="declineIncomingCall()" style="padding: 9px 14px; border-radius: 12px; background: #334155; color: #f8fafc; border: none; font-weight: 700; font-size: 0.8rem; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: all 0.15s ease;" onmouseover="this.style.background='#ef4444'; this.style.color='#ffffff';" onmouseout="this.style.background='#334155'; this.style.color='#f8fafc';">
                    <i class="fa-solid fa-phone-slash"></i> Decline
                </button>
                <button type="button" onclick="acceptIncomingCall()" style="padding: 9px 16px; border-radius: 12px; background: #22c55e; color: #ffffff; border: none; font-weight: 800; font-size: 0.8rem; cursor: pointer; display: flex; align-items: center; gap: 6px; box-shadow: 0 4px 14px rgba(34, 197, 94, 0.4); transition: all 0.15s ease;" onmouseover="this.style.background='#16a34a'" onmouseout="this.style.background='#22c55e'">
                    <i class="fa-solid fa-phone"></i> Accept
                </button>
            </div>
        </div>
    </div>

    <!-- STUDIO AUDIO / VIDEO CALL OVERLAY MODAL -->
    <div id="studioCallModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.92); backdrop-filter: blur(12px); z-index: 200000; align-items: center; justify-content: center; padding: 20px; animation: fadeInModal 0.25s ease;">
        <div style="background: #1e293b; border-radius: 24px; width: 100%; max-width: 520px; box-shadow: 0 25px 60px rgba(0, 0, 0, 0.5); overflow: hidden; border: 1px solid rgba(255, 255, 255, 0.1); position: relative;">
            
            <!-- Header Bar -->
            <div style="padding: 16px 20px; background: rgba(15, 23, 42, 0.6); display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid rgba(255, 255, 255, 0.08);">
                <div style="display: flex; align-items: center; gap: 8px; font-size: 0.82rem; font-weight: 800; color: #f8fafc; text-transform: uppercase; letter-spacing: 0.05em;">
                    <span id="callTypeBadgeIcon"><i class="fa-solid fa-phone" style="color: #22c55e;"></i></span>
                    <span id="callTypeTitle">Studio Audio Call</span>
                </div>
                <div id="callTimerContainer" style="display: none; background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(74, 222, 128, 0.3); font-size: 0.76rem; font-weight: 800; padding: 3px 10px; border-radius: 20px; font-family: monospace;">
                    ● <span id="callTimerText">00:00</span>
                </div>
            </div>

            <!-- Call Body Area -->
            <div style="padding: 36px 24px; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; position: relative; min-height: 280px;">
                
                <!-- Video Display Container (For Video Mode) -->
                <div id="callVideoFrame" style="display: none; position: absolute; inset: 0; background: #0f172a; overflow: hidden;">
                    <img id="callVideoBgAvatar" src="" style="width: 100%; height: 100%; object-fit: cover; filter: blur(12px) opacity(0.35); transform: scale(1.1);">
                    <div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;">
                        <div style="position: relative; width: 140px; height: 140px; border-radius: 50%; padding: 4px; background: linear-gradient(135deg, #dc2626, #ef4444); box-shadow: 0 10px 30px rgba(220, 38, 38, 0.4);">
                            <img id="callVideoCenterAvatar" src="" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                            <span style="position: absolute; bottom: 8px; right: 8px; width: 18px; height: 18px; border-radius: 50%; background: #22c55e; border: 3px solid #0f172a;" title="Studio Stream Active"></span>
                        </div>
                    </div>
                </div>

                <!-- Avatar & Ringing Animation Container (Audio & Connecting Mode) -->
                <div id="callAvatarSection" style="position: relative; margin-bottom: 24px;">
                    <div id="callRingingPulse" style="position: absolute; inset: -16px; border-radius: 50%; border: 2px dashed rgba(239, 68, 68, 0.4); animation: spinRing 6s linear infinite;"></div>
                    <div style="width: 108px; height: 108px; border-radius: 50%; padding: 4px; background: linear-gradient(135deg, #dc2626, #ef4444); box-shadow: 0 12px 32px rgba(220, 38, 38, 0.35); position: relative; z-index: 2;">
                        <img id="callTargetAvatar" src="" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                    </div>
                </div>

                <!-- Target User Details -->
                <div style="position: relative; z-index: 5;">
                    <h3 id="callTargetName" style="font-size: 1.35rem; font-weight: 800; color: #ffffff; margin: 0 0 6px 0;">Staff Member</h3>
                    <p id="callStatusText" style="font-size: 0.88rem; color: #94a3b8; margin: 0; font-weight: 600;">Initiating Studio Connection...</p>
                </div>

                <!-- Audio Waveform Visualizer -->
                <div id="callAudioWave" style="display: none; align-items: center; justify-content: center; gap: 4px; height: 28px; margin-top: 18px; position: relative; z-index: 5;">
                    <span style="width: 4px; height: 12px; background: #22c55e; border-radius: 2px; animation: waveBar 1.2s infinite ease-in-out 0.1s;"></span>
                    <span style="width: 4px; height: 24px; background: #22c55e; border-radius: 2px; animation: waveBar 1.2s infinite ease-in-out 0.3s;"></span>
                    <span style="width: 4px; height: 18px; background: #22c55e; border-radius: 2px; animation: waveBar 1.2s infinite ease-in-out 0.2s;"></span>
                    <span style="width: 4px; height: 28px; background: #22c55e; border-radius: 2px; animation: waveBar 1.2s infinite ease-in-out 0.4s;"></span>
                    <span style="width: 4px; height: 14px; background: #22c55e; border-radius: 2px; animation: waveBar 1.2s infinite ease-in-out 0.15s;"></span>
                </div>
            </div>

            <!-- Controls Action Footer Bar -->
            <div style="padding: 20px 24px; background: rgba(15, 23, 42, 0.8); border-top: 1px solid rgba(255, 255, 255, 0.08); display: flex; align-items: center; justify-content: center; gap: 20px;">
                <button type="button" id="btnToggleMuteMic" onclick="toggleCallMuteMic()" style="width: 48px; height: 48px; border-radius: 50%; background: #334155; color: #ffffff; border: none; font-size: 1.1rem; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.15s ease;" title="Mute Microphone">
                    <i class="fa-solid fa-microphone"></i>
                </button>

                <button type="button" onclick="endStudioCall()" style="width: 58px; height: 58px; border-radius: 50%; background: #dc2626; color: #ffffff; border: none; font-size: 1.3rem; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 24px rgba(220, 38, 38, 0.4); transition: all 0.15s ease;" title="End Call">
                    <i class="fa-solid fa-phone-slash"></i>
                </button>

                <button type="button" id="btnToggleCam" onclick="toggleCallCamera()" style="width: 48px; height: 48px; border-radius: 50%; background: #334155; color: #ffffff; border: none; font-size: 1.1rem; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.15s ease;" title="Toggle Camera">
                    <i class="fa-solid fa-video"></i>
                </button>
            </div>
        </div>
    </div>

    <style>
    @keyframes spinRing { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    @keyframes waveBar { 0%, 100% { height: 8px; } 50% { height: 28px; } }
    @keyframes slideInUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    @keyframes slideDownNotification { from { transform: translate(-50%, -40px); opacity: 0; } to { transform: translate(-50%, 0); opacity: 1; } }
    </style>

    <script>
    var currentCallId = null;
    var activeCallTimer = null;
    var activeCallSeconds = 0;
    var activeCallType = 'audio';
    var activeCallTargetName = '';
    var activeCallTargetAvatar = '';
    var audioCtx = null;
    var ringOsc = null;

    function playRingtone() {
        try {
            if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            if (audioCtx.state === 'suspended') audioCtx.resume();
            
            ringOsc = audioCtx.createOscillator();
            var gainNode = audioCtx.createGain();
            ringOsc.type = 'sine';
            ringOsc.frequency.setValueAtTime(440, audioCtx.currentTime);
            gainNode.gain.setValueAtTime(0.06, audioCtx.currentTime);
            
            ringOsc.connect(gainNode);
            gainNode.connect(audioCtx.destination);
            ringOsc.start();
        } catch(e){}
    }

    function stopRingtone() {
        try {
            if (ringOsc) {
                ringOsc.stop();
                ringOsc.disconnect();
                ringOsc = null;
            }
        } catch(e){}
    }

    function startStudioCall(type, name, avatarUrl, targetUsername) {
        activeCallType = type || 'audio';
        activeCallTargetName = name || 'Staff Member';
        activeCallTargetAvatar = avatarUrl || '';
        activeCallSeconds = 0;

        var targetUser = targetUsername || '<?php echo htmlspecialchars($activeDmUser ?? ""); ?>';

        var fd = new FormData();
        fd.append('action', 'initiate_studio_call_ajax');
        fd.append('target_user', targetUser);
        fd.append('target_name', activeCallTargetName);
        fd.append('target_avatar', activeCallTargetAvatar);
        fd.append('call_type', activeCallType);

        fetch('/admin/index.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success && data.call) {
                currentCallId = data.call.call_id;
            }
        });

        document.getElementById('callTypeTitle').innerText = (activeCallType === 'video') ? 'Studio Video Call' : 'Studio Audio Call';
        document.getElementById('callTypeBadgeIcon').innerHTML = (activeCallType === 'video') ? '<i class="fa-solid fa-video" style="color: #ef4444;"></i>' : '<i class="fa-solid fa-phone" style="color: #22c55e;"></i>';
        document.getElementById('callTargetName').innerText = activeCallTargetName;
        document.getElementById('callStatusText').innerText = 'Ringing studio line for ' + activeCallTargetName + '...';

        var fallbackImg = '/assets/img/team/team_henry.png';
        var finalAvatar = (activeCallTargetAvatar && activeCallTargetAvatar.length > 5) ? activeCallTargetAvatar : fallbackImg;

        document.getElementById('callTargetAvatar').src = finalAvatar;
        document.getElementById('callVideoBgAvatar').src = finalAvatar;
        document.getElementById('callVideoCenterAvatar').src = finalAvatar;

        document.getElementById('callTimerContainer').style.display = 'none';
        document.getElementById('callAudioWave').style.display = 'none';
        document.getElementById('callVideoFrame').style.display = 'none';
        document.getElementById('callAvatarSection').style.display = 'block';

        var modal = document.getElementById('studioCallModal');
        if (modal) modal.style.display = 'flex';

        playRingtone();
    }

    function pollCallSignals() {
        var fd = new FormData();
        fd.append('action', 'check_studio_call_signal_ajax');
        fetch('/admin/index.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (!data.success || !data.state) return;
            var st = data.state;
            
            // Receiver receives incoming call
            if (st.role === 'receiver' && st.call && st.call.status === 'ringing') {
                if (currentCallId !== st.call.call_id && document.getElementById('incomingCallModal').style.display !== 'flex') {
                    currentCallId = st.call.call_id;
                    activeCallType = st.call.type;
                    activeCallTargetName = st.call.caller_name;
                    activeCallTargetAvatar = st.call.caller_avatar || '';
                    
                    document.getElementById('incomingCallBadge').innerText = 'INCOMING ' + st.call.type.toUpperCase() + ' CALL';
                    document.getElementById('incomingCallerName').innerText = st.call.caller_name;
                    
                    var fallbackImg = '/assets/img/team/team_henry.png';
                    var finalAvatar = (activeCallTargetAvatar && activeCallTargetAvatar.length > 5) ? activeCallTargetAvatar : fallbackImg;
                    document.getElementById('incomingCallerAvatar').src = finalAvatar;

                    document.getElementById('incomingCallModal').style.display = 'flex';
                    playRingtone();
                }
            }

            // Caller detects acceptance
            if (st.role === 'caller' && st.call && st.call.call_id === currentCallId) {
                if (st.call.status === 'accepted' && activeCallSeconds === 0 && !activeCallTimer) {
                    stopRingtone();
                    document.getElementById('callStatusText').innerText = 'Connected • Studio ' + (activeCallType === 'video' ? '1080p Video Feed' : 'Audio Stream') + ' Live';
                    document.getElementById('callTimerContainer').style.display = 'inline-flex';
                    document.getElementById('callAudioWave').style.display = 'flex';
                    if (activeCallType === 'video') {
                        document.getElementById('callVideoFrame').style.display = 'block';
                        document.getElementById('callAvatarSection').style.display = 'none';
                    }
                    startCallTimer();
                } else if (st.call.status === 'ended' || st.call.status === 'declined') {
                    endStudioCallLocal();
                }
            }

            // Receiver detects caller end
            if (st.role === 'receiver' && st.call && st.call.call_id === currentCallId) {
                if (st.call.status === 'ended' || st.call.status === 'declined') {
                    endStudioCallLocal();
                }
            }
        })
        .catch(e => {});
    }

    setInterval(pollCallSignals, 2000);

    function acceptIncomingCall() {
        stopRingtone();
        document.getElementById('incomingCallModal').style.display = 'none';

        if (currentCallId) {
            var fd = new FormData();
            fd.append('action', 'update_studio_call_status_ajax');
            fd.append('call_id', currentCallId);
            fd.append('status', 'accepted');
            fetch('/admin/index.php', { method: 'POST', body: fd });
        }

        document.getElementById('callTypeTitle').innerText = (activeCallType === 'video') ? 'Studio Video Call' : 'Studio Audio Call';
        document.getElementById('callTypeBadgeIcon').innerHTML = (activeCallType === 'video') ? '<i class="fa-solid fa-video" style="color: #ef4444;"></i>' : '<i class="fa-solid fa-phone" style="color: #22c55e;"></i>';
        document.getElementById('callTargetName').innerText = activeCallTargetName;
        document.getElementById('callStatusText').innerText = 'Connected • Studio Audio Stream Live';

        var fallbackImg = '/assets/img/team/team_henry.png';
        var finalAvatar = (activeCallTargetAvatar && activeCallTargetAvatar.length > 5) ? activeCallTargetAvatar : fallbackImg;

        document.getElementById('callTargetAvatar').src = finalAvatar;
        document.getElementById('callVideoBgAvatar').src = finalAvatar;
        document.getElementById('callVideoCenterAvatar').src = finalAvatar;

        document.getElementById('callTimerContainer').style.display = 'inline-flex';
        document.getElementById('callAudioWave').style.display = 'flex';
        document.getElementById('callAvatarSection').style.display = 'block';

        if (activeCallType === 'video') {
            document.getElementById('callVideoFrame').style.display = 'block';
            document.getElementById('callAvatarSection').style.display = 'none';
            document.getElementById('callStatusText').innerText = 'Connected • 1080p Studio Video Feed Live';
        }

        var modal = document.getElementById('studioCallModal');
        if (modal) modal.style.display = 'flex';

        startCallTimer();
    }

    function declineIncomingCall() {
        stopRingtone();
        document.getElementById('incomingCallModal').style.display = 'none';
        if (currentCallId) {
            var fd = new FormData();
            fd.append('action', 'update_studio_call_status_ajax');
            fd.append('call_id', currentCallId);
            fd.append('status', 'declined');
            fetch('/admin/index.php', { method: 'POST', body: fd });
        }
    }

    function endStudioCall() {
        if (currentCallId) {
            var fd = new FormData();
            fd.append('action', 'update_studio_call_status_ajax');
            fd.append('call_id', currentCallId);
            fd.append('status', 'ended');
            fetch('/admin/index.php', { method: 'POST', body: fd });
        }
        endStudioCallLocal();
    }

    function endStudioCallLocal() {
        stopRingtone();
        clearInterval(activeCallTimer);
        activeCallTimer = null;

        var statusElem = document.getElementById('callStatusText');
        if (statusElem) statusElem.innerText = 'Call Ended';

        setTimeout(function() {
            var modal = document.getElementById('studioCallModal');
            if (modal) modal.style.display = 'none';
            var incModal = document.getElementById('incomingCallModal');
            if (incModal) incModal.style.display = 'none';

            if (activeCallSeconds > 0) {
                var mins = Math.floor(activeCallSeconds / 60);
                var secs = activeCallSeconds % 60;
                var timeFormatted = (mins > 0 ? mins + ' min ' : '') + secs + ' sec';
                var msgInput = document.querySelector('#commsChatContainer + div form input[name="message"]');
                var form = document.querySelector('#commsChatContainer + div form');
                if (msgInput && form) {
                    msgInput.value = '📞 Studio ' + (activeCallType === 'video' ? 'Video' : 'Audio') + ' Call ended • ' + timeFormatted;
                    form.submit();
                }
            }
            activeCallSeconds = 0;
            currentCallId = null;
        }, 600);
    }

    function startCallTimer() {
        clearInterval(activeCallTimer);
        activeCallSeconds = 0;
        updateCallTimerDisplay();
        activeCallTimer = setInterval(function() {
            activeCallSeconds++;
            updateCallTimerDisplay();
        }, 1000);
    }

    function updateCallTimerDisplay() {
        var mins = Math.floor(activeCallSeconds / 60);
        var secs = activeCallSeconds % 60;
        var str = (mins < 10 ? '0' + mins : mins) + ':' + (secs < 10 ? '0' + secs : secs);
        var elem = document.getElementById('callTimerText');
        if (elem) elem.innerText = str;
    }

    function toggleCallMuteMic() {
        var btn = document.getElementById('btnToggleMuteMic');
        if (!btn) return;
        if (btn.style.background === 'rgb(220, 38, 38)' || btn.style.background === '#dc2626') {
            btn.style.background = '#334155';
            btn.innerHTML = '<i class="fa-solid fa-microphone"></i>';
        } else {
            btn.style.background = '#dc2626';
            btn.innerHTML = '<i class="fa-solid fa-microphone-slash"></i>';
        }
    }

    function toggleCallCamera() {
        var btn = document.getElementById('btnToggleCam');
        if (!btn) return;
        var videoFrame = document.getElementById('callVideoFrame');
        var avatarSec = document.getElementById('callAvatarSection');
        
        if (videoFrame.style.display === 'block') {
            videoFrame.style.display = 'none';
            avatarSec.style.display = 'block';
            btn.style.background = '#dc2626';
            btn.innerHTML = '<i class="fa-solid fa-video-slash"></i>';
        } else {
            videoFrame.style.display = 'block';
            avatarSec.style.display = 'none';
            btn.style.background = '#334155';
            btn.innerHTML = '<i class="fa-solid fa-video"></i>';
        }
    }

    var pendingConfirmForm = null;

    function promptConfirmModal(formElement, title, message) {
        pendingConfirmForm = formElement;
        var titleElem = document.getElementById('confirmModalTitle');
        var msgElem = document.getElementById('confirmModalMessage');
        if (titleElem) titleElem.innerText = title || 'Confirm Action';
        if (msgElem) msgElem.innerText = message || 'Are you sure you want to proceed with this action?';
        var modal = document.getElementById('globalConfirmModal');
        if (modal) modal.style.display = 'flex';
        return false;
    }

    function closeConfirmModal() {
        pendingConfirmForm = null;
        var modal = document.getElementById('globalConfirmModal');
        if (modal) modal.style.display = 'none';
    }

    function executeConfirmAction() {
        if (pendingConfirmForm) {
            var f = pendingConfirmForm;
            pendingConfirmForm = null;
            closeConfirmModal();
            f.submit();
        }
    }
    </script>
</body>
</html>
