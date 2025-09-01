<?php

namespace App\Domain\Constant;

class NotifyHistoryConstant
{
    const TRIGGER_CONTACT_MICROSOFT = 'contact-microsoft';
    const TRIGGER_USER_TOKEN = 'user-token';
    const TRIGGER_REQUEST_APPLICATION = 'request';
    const TRIGGER_APPROVABLE_APPLICATION = 'approved';
    const TRIGGER_APPROVED_APPLICATION = 'approved';
    const TRIGGER_RETURN_APPLICANT = 'return-applicant';
    const TRIGGER_RETURN_APPROVER = 'return-approver';
    const TRIGGER_UPDATE_APPLICATION = 'delete-application';
    const TRIGGER_DENY_APPLICATION = 'deny-application';
    const TRIGGER_UNDONE_APPLICANT = 'undone-applicant';
    const TRIGGER_UNDONE_APPROVER = 'undone-approver';
    const TRIGGER_UNDONE_SURVEY = 'undone-survey';
}
