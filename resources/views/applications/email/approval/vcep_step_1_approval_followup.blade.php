Dear {{$notifiable->name}},

<p>Congratulations on your approval! Just want to follow up with some housekeeping items.</p>

<p>
    <strong>ClinGen Web Page Development:</strong> 
    I have used the information in your application to set up a page on clinicalgenome.org for your group at <a href="{{$expertPanel->clingenUrl}}">{{$expertPanel->clingenUrl}}</a>, please review and update as needed. The page is currently hidden, and can be made public with your approval. If we do not hear back from you within two weeks, we will go ahead and make the page public. If you need help with any changes, just let me know. 
</p>

<p>I can also set up a representative from your VCEP with an admin account to edit the website, please let me know if that’s of interest and who your representative will be.</p>

<p>
    <strong>VCEP Step 2 Guidance:</strong> 
    The next step in the ClinGen VCEP application process is Step 2 - Develop Variant Classification Rules. Please refer to Section 2.2 starting on page 10 of the ClinGen VCEP Protocol for detailed guidance on this step. For further guidance on getting started, please reach out to your ClinGen PI liaison and/or parent CDWG coordinator.
</p>

</p>
    <strong>Curation Interface Access:</strong> 
    An affiliation will be set up for you in the next Variant Curation Interface release if it hasn't been already. Please contact <a href="clingen-helpdesk@lists.stanford.edu">clingen-helpdesk@lists.stanford.edu</a> with the list of names and email addresses of everyone who will need an account for the VCI and to be added to your affiliation when you are reading to start curating.
</p>

<p>
    <strong>Variant Curation Training (VCI):</strong> 
    When your VCEP is ready to start curating in the VCI, contact the Community Curation Committee (<a href="volunteer@clinicalgenome.org">volunteer@clinicalgenome.org</a>) to see if any Variant Curation Interface (VCI) training sessions are scheduled, if not, they will work with you to set up appropriate training for your curators. 
</p>

Thank you,

{{Auth::user()->name}}