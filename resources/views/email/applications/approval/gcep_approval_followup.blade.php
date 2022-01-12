Dear {{$notifiable->name}},

<p>Congratulations on your approval! Just want to follow up with some housekeeping items.</p>

<p>
    <strong>Curation Interface Access:</strong> 
    An affiliation will be set up for you in the Gene Curation Interface if it hasn't been already. 
    Please contact <a href="mailto:clingen-helpdesk@lists.stanford.edu">clingen-helpdesk@lists.stanford.edu</a> 
    with the list of names and email addresses of everyone who will need an account for the GCI and to be added to your affiliation.
</p>

<p>
    <strong>Gene Curation Training:</strong> 
    If anyone in your GCEP would like to join a training session for using the GCI, 
    please contact <a href="mailto:volunteer@clinicalgenome.org">volunteer@clinicalgenome.org</a> 
    to set them up with training. Resources for Gene Curation training can be found here: 
    <a href="https://www.clinicalgenome.org/curation-activities/gene-disease-validity/training-materials/">
        https://www.clinicalgenome.org/curation-activities/gene-disease-validity/training-materials/
    </a>
</p>

<p>
    <strong>ClinGen Web Page Development:</strong> 
    I have used the information in your application to set up a page on clinicalgenome.org for your group at
    <a href="{{$expertPanel->clingenUrl}}">{{$expertPanel->clingenUrl}}</a>, 
    please review and update as needed. The page is currently hidden, and can be made public with your approval. If we do not hear back from you within two weeks, we will go ahead and make the page public. If you need help with any changes, just let me know. 
</p>

<p>
    I can also set up a representative from your GCEP with an admin account to edit the website, please let me know if that’s of interest and who your representative will be.
</p>

Thank you,

{{Auth::user()->name}}