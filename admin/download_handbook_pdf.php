<?php
/**
 * Falhen Media - Dynamic Staff Handbook PDF / Print Generator
 */
session_start();
require_once __DIR__ . '/../includes/functions.php';

$currentUsername = $_SESSION['admin_username'] ?? $_SESSION['username'] ?? '';
$currentRole = $_SESSION['admin_role'] ?? $_SESSION['role'] ?? 'Staff';
$currentEmail = $_SESSION['admin_email'] ?? $_SESSION['email'] ?? '';

if (empty($currentUsername)) {
    header('Location: /admin/login.php');
    exit;
}

$isHRorAdmin = isAdminUser($currentRole, $currentEmail, $currentUsername);
$targetUser = trim($_GET['target_user'] ?? $currentUsername);

if (!$isHRorAdmin && strtolower($currentUsername) !== strtolower($targetUser)) {
    $targetUser = $currentUsername;
}

$onboardingData = getUserOnboardingData($targetUser);
$handbookData = $onboardingData['staff_handbook'] ?? [];
$staffObj = getStaffAccountByUsername($targetUser);
$userFullName = $staffObj['full_name'] ?? ucwords(str_replace('.', ' ', $targetUser));

$handbookAcknowledged = !empty($handbookData['acknowledged']);
$handbookAckAt = $handbookData['acknowledged_at'] ?? date('Y-m-d H:i:s');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Handbook & Policies - <?php echo htmlspecialchars($userFullName); ?> | Falhen Media</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @page { size: A4; margin: 18mm; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: #0f172a;
            background: #f8fafc;
            margin: 0;
            padding: 40px 20px;
            line-height: 1.6;
            font-size: 0.88rem;
        }
        .page-container {
            max-width: 850px;
            margin: 0 auto;
            background: #ffffff;
            padding: 40px 48px;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
            border: 1px solid #e2e8f0;
        }
        .header-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #dc2626;
            padding-bottom: 20px;
            margin-bottom: 24px;
        }
        .action-bar {
            max-width: 850px;
            margin: 0 auto 20px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn-print {
            background: #0f172a;
            color: #ffffff;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.88rem;
            cursor: pointer;
            border: none;
        }
        h3 {
            font-size: 0.98rem;
            font-weight: 700;
            color: #0f172a;
            margin-top: 22px;
            margin-bottom: 8px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
        }
        h4 {
            font-size: 0.9rem;
            font-weight: 700;
            color: #334155;
            margin-top: 14px;
            margin-bottom: 6px;
        }
        ul { margin-top: 6px; margin-bottom: 12px; padding-left: 20px; }
        li { margin-bottom: 4px; }
        @media print {
            body { background: #ffffff; padding: 0; }
            .page-container { box-shadow: none; border: none; padding: 0; }
            .action-bar { display: none !important; }
        }
    </style>
</head>
<body>

    <div class="action-bar">
        <a href="/admin/index.php?section=onboarding" style="color: #64748b; text-decoration: none; font-size: 0.88rem; font-weight: 700;">
            <i class="fa-solid fa-arrow-left"></i> Back to Onboarding
        </a>
        <button onclick="window.print()" class="btn-print">
            <i class="fa-solid fa-print"></i> Print / Save Handbook PDF
        </button>
    </div>

    <div class="page-container">
        <div class="header-box">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 42px; height: 42px; border-radius: 10px; background: #dc2626; color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; font-weight: 900;">F</div>
                <div>
                    <div style="font-size: 1.4rem; font-weight: 900; color: #0f172a;">Falhen <span style="color: #dc2626;">Media</span></div>
                    <div style="font-size: 0.78rem; color: #64748b;">Human Resources & Talent Management</div>
                </div>
            </div>
            <div style="text-align: right; font-size: 0.82rem; color: #64748b;">
                <div><strong>Document Ref:</strong> FLH-HBK-2026</div>
                <div><strong>Effective Date:</strong> January 1, 2026</div>
            </div>
        </div>

        <div style="text-align: center; margin-bottom: 28px;">
            <div style="font-size: 1.5rem; font-weight: 900; color: #0f172a;">STAFF HANDBOOK & COMPANY POLICIES</div>
            <div style="font-size: 0.85rem; color: #64748b; margin-top: 4px;">Employee Guide to Expectations, Benefits, Conduct & Culture (30 Sections)</div>
        </div>

        <h3>1. Welcome Message</h3>
        <p>Welcome to Falhen.</p>
        <p>We are pleased to have you as part of our team. At Falhen, we believe that our people are our greatest asset. This Staff Handbook has been created to guide employees on the company’s expectations, policies, procedures, benefits, code of conduct, and workplace culture.</p>
        <p>This handbook is designed to help every employee understand how we work, what we stand for, and how we can collectively build a productive, respectful, and excellent organization.</p>
        <p>All employees are expected to read, understand, and comply with the policies contained in this handbook.</p>

        <h3>2. About Falhen</h3>
        <p>Falhen is committed to providing excellent services, building strong internal systems, and creating a workplace where employees can contribute meaningfully, grow professionally, and perform at their best.</p>
        <p>Our operations are guided by professionalism, accountability, innovation, teamwork, integrity, and continuous improvement.</p>

        <h3>3. Vision Statement</h3>
        <p>To build a reputable, efficient, and people-centered organization known for excellence, innovation, and consistent service delivery.</p>

        <h3>4. Mission Statement</h3>
        <p>To deliver quality solutions through a committed workforce, strong operational systems, and a culture of accountability, excellence, and continuous growth.</p>

        <h3>5. Core Values</h3>
        <p>Falhen is guided by the following values:</p>
        <h4>Integrity</h4>
        <p>We act honestly, ethically, and responsibly in all our dealings.</p>
        <h4>Excellence</h4>
        <p>We strive to deliver quality work and maintain high standards in everything we do.</p>
        <h4>Accountability</h4>
        <p>We take responsibility for our actions, decisions, duties, and results.</p>
        <h4>Teamwork</h4>
        <p>We collaborate respectfully and support one another to achieve common goals.</p>
        <h4>Innovation</h4>
        <p>We embrace new ideas, technology, and better ways of working.</p>
        <h4>Professionalism</h4>
        <p>We conduct ourselves with respect, discipline, and a strong sense of duty.</p>

        <h3>6. Employment Policy</h3>
        <p>Employment at Falhen is based on merit, competence, character, and the ability to perform assigned responsibilities.</p>
        <p>All employees are expected to provide accurate information during recruitment and onboarding. Any false information, forged document, or misrepresentation may lead to disciplinary action or termination of employment.</p>
        <p>Employment categories may include:</p>
        <ul>
            <li>Full-time employees</li>
            <li>Part-time employees</li>
            <li>Contract employees</li>
            <li>Temporary employees</li>
            <li>Interns</li>
            <li>Consultants</li>
        </ul>
        <p>Each employee’s employment status, role, compensation, and benefits shall be stated in their appointment letter or employment contract.</p>

        <h3>7. Equal Opportunity Policy</h3>
        <p>Falhen is committed to maintaining a fair and inclusive workplace.</p>
        <p>Employment decisions shall be made without discrimination based on gender, age, ethnic background, religion, disability, marital status, or any other protected status under applicable law.</p>
        <p>All employees are expected to treat one another with dignity, fairness, and respect.</p>

        <h3>8. Work Hours</h3>
        <p>Official working hours shall be communicated by management and may vary depending on department, role, shift, or operational needs.</p>
        <p>Employees are expected to:</p>
        <ul>
            <li>Resume work at the approved time</li>
            <li>Remain available during working hours</li>
            <li>Attend scheduled meetings</li>
            <li>Complete assigned tasks within agreed timelines</li>
            <li>Notify their supervisor if they will be late or unavailable</li>
        </ul>
        <p>Any changes to working hours, shifts, or work arrangements must be approved by management.</p>

        <h3>9. Attendance and Punctuality</h3>
        <p>Regular attendance and punctuality are important to Falhen’s operations.</p>
        <p>Employees are required to record their attendance through the approved attendance system or HR portal.</p>
        <h4>Check-In</h4>
        <p>Employees must check in when they resume work.</p>
        <h4>Break</h4>
        <p>Employees should observe approved break periods responsibly and return to work on time.</p>
        <h4>Check-Out</h4>
        <p>Employees must check out at the close of work or after completing their approved schedule.</p>
        <p>Failure to check in or check out may affect attendance records unless reported and approved by HR or the employee’s supervisor.</p>
        <p>Repeated lateness, absenteeism, or poor attendance may result in disciplinary action.</p>

        <h3>10. Remote Work and Flexible Work</h3>
        <p>Where applicable, Falhen may allow remote work or flexible work arrangements based on role requirements, company policy, and management approval.</p>
        <p>Employees working remotely are expected to:</p>
        <ul>
            <li>Be available during working hours</li>
            <li>Attend virtual meetings</li>
            <li>Submit work reports</li>
            <li>Protect company data</li>
            <li>Maintain regular communication with supervisors</li>
            <li>Deliver assigned tasks on time</li>
        </ul>
        <p>Remote work is a privilege and may be reviewed, modified, or withdrawn by management.</p>

        <h3>11. Leave Policy</h3>
        <p>Falhen recognizes that employees may need time away from work for rest, health, family, emergencies, or other approved reasons.</p>
        <p>All leave requests must be submitted through the approved HR portal or official channel.</p>
        <p>Types of leave may include:</p>
        <ul>
            <li>Annual Leave</li>
            <li>Sick Leave</li>
            <li>Emergency Leave</li>
            <li>Maternity Leave</li>
            <li>Paternity Leave</li>
            <li>Compassionate Leave</li>
            <li>Study Leave</li>
            <li>Unpaid Leave</li>
        </ul>
        <p>Employees must not proceed on leave until approval has been granted, except in genuine emergencies or health-related situations where immediate notice should be given.</p>
        <p>Leave approval shall depend on leave entitlement, departmental workload, operational needs, and company policy.</p>

        <h3>12. Public Holidays</h3>
        <p>Falhen shall observe approved public holidays as declared by relevant authorities, subject to business operations and management discretion.</p>
        <p>Employees required to work on public holidays may be compensated or granted time off in accordance with company policy.</p>

        <h3>13. Compensation and Salary</h3>
        <p>Employees shall be paid based on the terms stated in their employment letter or contract.</p>
        <p>Salary payment shall be made through approved payment channels and within the company’s scheduled payroll period.</p>
        <p>Employees are expected to keep their bank and personal information updated with HR.</p>
        <p>Salary details are confidential and should not be disclosed or discussed inappropriately within the workplace.</p>

        <h3>14. Benefits and Welfare</h3>
        <p>Falhen may provide employee benefits based on company policy, employment category, role, and availability of resources.</p>
        <p>Benefits may include:</p>
        <ul>
            <li>Leave entitlement</li>
            <li>Training and development opportunities</li>
            <li>Health and wellness support</li>
            <li>Performance incentives</li>
            <li>Work tools</li>
            <li>Staff welfare support</li>
            <li>Other approved benefits</li>
        </ul>
        <p>Benefits may be reviewed periodically by management.</p>

        <h3>15. Performance Management</h3>
        <p>Falhen is committed to helping employees perform effectively and grow professionally.</p>
        <p>Employee performance may be assessed based on:</p>
        <ul>
            <li>Job knowledge</li>
            <li>Quality of work</li>
            <li>Productivity</li>
            <li>Attendance and punctuality</li>
            <li>Teamwork</li>
            <li>Communication</li>
            <li>Initiative</li>
            <li>Professional conduct</li>
            <li>Achievement of targets</li>
            <li>Compliance with company policies</li>
        </ul>
        <p>Performance reviews may be conducted monthly, quarterly, annually, or as determined by management.</p>
        <p>Employees are expected to receive feedback positively and work towards continuous improvement.</p>

        <h3>16. Training and Development</h3>
        <p>Falhen encourages continuous learning and professional growth.</p>
        <p>Employees may be required to attend internal or external training programs to improve their skills and performance.</p>
        <p>Training opportunities may be based on:</p>
        <ul>
            <li>Job requirements</li>
            <li>Performance needs</li>
            <li>Departmental goals</li>
            <li>Company priorities</li>
            <li>Employee development plans</li>
        </ul>
        <p>Employees who attend company-sponsored training may be required to share knowledge, submit reports, or apply the training to their role.</p>

        <h3>17. Code of Conduct</h3>
        <p>Employees are expected to conduct themselves professionally at all times.</p>
        <p>Employees must:</p>
        <ul>
            <li>Be respectful and courteous</li>
            <li>Perform assigned duties diligently</li>
            <li>Obey lawful instructions from supervisors</li>
            <li>Protect company property and information</li>
            <li>Avoid conflicts of interest</li>
            <li>Maintain confidentiality</li>
            <li>Avoid gossip, hostility, or disruptive behavior</li>
            <li>Represent Falhen positively</li>
            <li>Comply with company policies and procedures</li>
        </ul>
        <p>Any conduct that damages the company’s reputation, disrupts operations, or affects workplace harmony may result in disciplinary action.</p>

        <h3>18. Dress Code and Appearance</h3>
        <p>Employees are expected to dress neatly, modestly, and appropriately for their roles and work environment.</p>
        <p>The acceptable dress code may vary depending on department, client-facing duties, events, field work, or official assignments.</p>
        <p>Employees representing Falhen at meetings, events, or client engagements must maintain a professional appearance.</p>

        <h3>19. Communication Policy</h3>
        <p>Clear and respectful communication is required across all levels of Falhen.</p>
        <p>Approved communication channels may include:</p>
        <ul>
            <li>Company email</li>
            <li>HR portal</li>
            <li>Internal communication platform</li>
            <li>Official phone calls</li>
            <li>Departmental meetings</li>
            <li>Approved messaging groups</li>
            <li>Official memos</li>
        </ul>
        <p>Employees must avoid using unofficial channels for sensitive, confidential, or management-level communication.</p>
        <p>All official communication should be professional, clear, respectful, and timely.</p>

        <h3>20. Use of Company Property</h3>
        <p>Company property must be used responsibly and only for approved business purposes.</p>
        <p>Company property may include:</p>
        <ul>
            <li>Laptops</li>
            <li>Mobile devices</li>
            <li>Office equipment</li>
            <li>Vehicles</li>
            <li>Software</li>
            <li>Documents</li>
            <li>Access cards</li>
            <li>Company email accounts</li>
            <li>Login credentials</li>
            <li>Work tools</li>
        </ul>
        <p>Employees are responsible for protecting company property assigned to them. Loss, damage, misuse, or unauthorized use may attract disciplinary or financial consequences.</p>

        <h3>21. Information Technology and System Use</h3>
        <p>Employees must use company systems responsibly and securely.</p>
        <p>Employees are expected to:</p>
        <ul>
            <li>Keep passwords confidential</li>
            <li>Avoid sharing login details</li>
            <li>Use strong passwords</li>
            <li>Log out from shared devices</li>
            <li>Report suspicious activity</li>
            <li>Avoid unauthorized software installation</li>
            <li>Protect company files and data</li>
            <li>Use company email and platforms professionally</li>
        </ul>
        <p>Unauthorized access, data theft, misuse of systems, or cyber negligence may result in disciplinary action.</p>

        <h3>22. Confidentiality Policy</h3>
        <p>Employees may have access to confidential company, client, staff, financial, or operational information.</p>
        <p>Confidential information must not be disclosed to unauthorized persons during or after employment.</p>
        <p>Confidential information includes:</p>
        <ul>
            <li>Business plans</li>
            <li>Financial records</li>
            <li>Staff information</li>
            <li>Client information</li>
            <li>Contracts</li>
            <li>Pricing information</li>
            <li>Passwords and access credentials</li>
            <li>Internal reports</li>
            <li>Company strategies</li>
            <li>Intellectual property</li>
        </ul>
        <p>Breach of confidentiality may lead to disciplinary action, termination, or legal action where necessary.</p>

        <h3>23. Conflict of Interest</h3>
        <p>Employees must avoid situations where personal interests conflict with the interests of Falhen.</p>
        <p>A conflict of interest may occur when an employee:</p>
        <ul>
            <li>Works for a competitor without approval</li>
            <li>Uses company resources for personal business</li>
            <li>Awards business to relatives or friends unfairly</li>
            <li>Accepts gifts that influence official decisions</li>
            <li>Engages in activities that affect job performance</li>
        </ul>
        <p>Employees must disclose any actual or potential conflict of interest to management.</p>

        <h3>24. Anti-Harassment and Respectful Workplace Policy</h3>
        <p>Falhen is committed to maintaining a safe, respectful, and harassment-free workplace.</p>
        <p>Harassment, intimidation, bullying, discrimination, victimization, or abusive behavior will not be tolerated.</p>
        <p>Employees who experience or witness inappropriate conduct should report it to HR, their supervisor, or management.</p>
        <p>All complaints shall be handled with confidentiality, fairness, and seriousness.</p>

        <h3>25. Health, Safety, and Welfare</h3>
        <p>Falhen is committed to providing a safe and healthy work environment.</p>
        <p>Employees are expected to:</p>
        <ul>
            <li>Follow safety instructions</li>
            <li>Report hazards or unsafe conditions</li>
            <li>Use equipment properly</li>
            <li>Keep the workplace clean</li>
            <li>Avoid actions that may endanger themselves or others</li>
            <li>Report accidents or injuries immediately</li>
        </ul>
        <p>Employees must not report to work under the influence of alcohol, illegal substances, or anything that may impair their judgment or performance.</p>

        <h3>26. Grievance Procedure</h3>
        <p>Employees are encouraged to raise concerns, complaints, or workplace issues through the proper channel.</p>
        <p>The grievance process should follow this order:</p>
        <ol style="padding-left: 20px; margin-top: 6px; margin-bottom: 12px; line-height: 1.7;">
            <li>Discuss with immediate supervisor</li>
            <li>Report to department head</li>
            <li>Submit complaint to HR/Admin</li>
            <li>Escalate to management if unresolved</li>
        </ol>
        <p>Falhen shall review grievances fairly and take appropriate action where necessary.</p>

        <h3>27. Disciplinary Policy</h3>
        <p>Disciplinary action may be taken when an employee violates company policy, fails to meet expectations, or engages in misconduct.</p>
        <p>Examples of misconduct include:</p>
        <ul>
            <li>Absenteeism without approval</li>
            <li>Repeated lateness</li>
            <li>Negligence of duty</li>
            <li>Insubordination</li>
            <li>Poor performance</li>
            <li>Harassment or bullying</li>
            <li>Dishonesty</li>
            <li>Misuse of company property</li>
            <li>Breach of confidentiality</li>
            <li>Fraud or theft</li>
            <li>Fighting or violence</li>
            <li>Unauthorized disclosure of company information</li>
        </ul>
        <p>Disciplinary actions may include:</p>
        <ul>
            <li>Verbal warning</li>
            <li>Written warning</li>
            <li>Suspension</li>
            <li>Performance improvement plan</li>
            <li>Demotion or reassignment</li>
            <li>Termination of employment</li>
        </ul>
        <p>Falhen shall aim to apply disciplinary actions fairly and consistently.</p>

        <h3>28. Resignation and Exit Procedure</h3>
        <p>Employees who wish to resign must submit a written resignation letter in accordance with their employment contract or notice period.</p>
        <p>Before exit, the employee must:</p>
        <ul>
            <li>Complete handover of duties</li>
            <li>Return company property</li>
            <li>Submit pending reports</li>
            <li>Clear outstanding obligations</li>
            <li>Transfer files and documents</li>
            <li>Attend exit interview, where required</li>
            <li>Ensure access to company systems is properly revoked</li>
        </ul>
        <p>Final clearance must be completed before final payment or release of employment documents, where applicable.</p>

        <h3>29. Termination of Employment</h3>
        <p>Falhen may terminate employment in accordance with the employee’s contract, company policy, and applicable law.</p>
        <p>Reasons for termination may include:</p>
        <ul>
            <li>Poor performance</li>
            <li>Misconduct</li>
            <li>Redundancy</li>
            <li>Breach of contract</li>
            <li>Operational restructuring</li>
            <li>Violation of company policy</li>
            <li>Loss of trust or confidence</li>
        </ul>
        <p>All terminations shall follow approved internal procedures.</p>

        <h3>30. Acknowledgement</h3>
        <p>All employees are required to acknowledge that they have received, read, understood, and agreed to comply with the Falhen Staff Handbook.</p>
        <p>This handbook may be reviewed or updated from time to time. Employees shall be notified of major changes where necessary.</p>

        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 18px; margin-top: 24px;">
            <h4 style="font-size: 0.95rem; font-weight: 700; color: #166534; margin: 0 0 6px 0;">Acknowledgement & Digital Signature</h4>
            <p style="margin: 0 0 8px 0;">I, <strong><?php echo htmlspecialchars($userFullName); ?></strong>, acknowledge that I have received, read, understood, and agreed to comply with the Falhen Staff Handbook & Policies.</p>
            <?php if ($handbookAcknowledged): ?>
                <?php if (!empty($handbookData['signature_data'])): ?>
                    <div style="margin: 8px 0; padding: 6px 12px; background: #ffffff; border: 1px dashed #bbf7d0; border-radius: 6px; display: inline-block;">
                        <div style="font-size: 0.7rem; color: #64748b; font-weight: 700; margin-bottom: 2px;">Employee Signature:</div>
                        <img src="<?php echo htmlspecialchars($handbookData['signature_data']); ?>" alt="Digital Signature" style="max-height: 50px; display: block;">
                    </div>
                <?php endif; ?>
                <div style="font-size: 0.82rem; color: #166534; font-weight: 700; margin-top: 4px;">
                    <i class="fa-solid fa-file-signature"></i> Digitally Acknowledged & Verified by <?php echo htmlspecialchars($userFullName); ?> on <?php echo date('M d, Y H:i', strtotime($handbookAckAt)); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        window.onload = function() {
            if (window.location.search.indexOf('print=1') !== -1) {
                setTimeout(function() { window.print(); }, 400);
            }
        };
    </script>
</body>
</html>
