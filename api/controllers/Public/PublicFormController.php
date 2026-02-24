<?php
/**
 * Public Form Controller
 *
 * Handles contact-us and business (merchant) sign-up enquiries.
 * No authentication required.
 *
 * POST /api/public/contact           → saves to contact_messages
 * POST /api/public/business-signup   → saves to business_signups
 */
class PublicFormController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ── POST /api/public/contact ──────────────────────────────────────────────
    public function contact(array $body): never
    {
        $name    = trim($body['name']    ?? '');
        $mobile  = trim($body['mobile']  ?? '');
        $subject = trim($body['subject'] ?? '');
        $message = trim($body['message'] ?? '');

        if (!$name)    Response::error('Name is required.', 422);
        if (!$mobile)  Response::error('Mobile number is required.', 422);
        if (!$subject) Response::error('Subject is required.', 422);
        if (!$message) Response::error('Message is required.', 422);

        if (!preg_match('/^[6-9]\d{9}$/', $mobile)) {
            Response::error('Enter a valid 10-digit Indian mobile number.', 422);
        }

        $this->db->execute(
            "INSERT INTO contact_messages (name, mobile, subject, message) VALUES (?, ?, ?, ?)",
            [$name, $mobile, $subject, $message]
        );

        Response::success([], 'Your message has been sent. We\'ll get back to you soon!');
    }

    // ── POST /api/public/business-signup ──────────────────────────────────────
    public function businessSignup(array $body): never
    {
        $contactName = trim($body['contact_name'] ?? '');
        $orgName     = trim($body['org_name']     ?? '');
        $category    = trim($body['category']     ?? '');
        $email       = trim($body['email']        ?? '');
        $phone       = trim($body['phone']        ?? '');
        $message     = trim($body['message']      ?? '');

        if (!$contactName) Response::error('Contact name is required.', 422);
        if (!$orgName)     Response::error('Business name is required.', 422);
        if (!$email)       Response::error('Email address is required.', 422);
        if (!$phone)       Response::error('Phone number is required.', 422);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('Enter a valid email address.', 422);
        }
        if (!preg_match('/^[6-9]\d{9}$/', $phone)) {
            Response::error('Enter a valid 10-digit Indian mobile number.', 422);
        }

        // Prevent duplicate submissions from same email
        $existing = $this->db->queryOne(
            "SELECT id FROM business_signups WHERE email = ? LIMIT 1",
            [$email]
        );
        if ($existing) {
            Response::error('We already have an enquiry from this email. Our team will contact you shortly.', 409);
        }

        $this->db->execute(
            "INSERT INTO business_signups (contact_name, org_name, category, email, phone, message)
             VALUES (?, ?, ?, ?, ?, ?)",
            [$contactName, $orgName, $category ?: null, $email, $phone, $message ?: null]
        );

        Response::success([], 'Thank you for your interest! Our merchant team will contact you within 24 hours.');
    }
}
