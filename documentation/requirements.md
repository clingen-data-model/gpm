# Expert Panel Management Requirements

[back to project readme](https://github.com/clingen-data-model/ep-management#readme)

The requirements below are based on interviews with multiple stakeholders representing multiple roles relevant to the creation and maintanence of Expert Panels.  This is intended to be a descriptive, not prescriptive description of user's needs to effectively manage ClinGen expert panels.

## Definition of terms and user roles
* ACMG - American college of edial Genetics and Genomics
  * ACMG/AMP Guidelines - General guidelines for interpreting the pathogenicity of variants.
  * ACMG/AMP guideline specifications (specifications) - VCEP specifications of the AMCG/AMP Guidelines applicable to the VCEP's genes of interest.
* CDWG (Clinical Domain Working Group) - 
* CDWG Oversight committee (OC) - 
* Co-chairs - 
* COI - Conflict of interest`
* Expert Panel (EP) - 
* EP Members - Members of an expert panel  
Members include:
  * Coordinator - An EP member who coordinates the activities of the expert panel.  This is usually the point person on the EP for the application process
  * Biocurator - Does biocuration for the EP
  * 
* GCEP - Gene Curation Expert Panel
* SOP - Standard Operating Procedure
* SVI - Sequence Variant Interpretation
  * Rule Specification Review Committee - Reviews and approves VCEP ACMG specifications 
* VCEP - Variant Curation Expert Panel


## Expert Panel Application Management
The EP application process differs between GCEPs and VCEPs.  GCEPs only need to define the scope and membership of their group (EP Definition).  VCEPs must also develop and pilot AMCG guidelines.  

### General
* Authorized users should be able to move an application from one step to another.
* Authorized users should be able to see a list of all EPs and their their position in the application process.
* Authorized users should be able to see a list of all EPs and their position in the application process.
* Authorized users should be able to attach files (Word, Excel, PowerPoint, PDF, etc) to an EP.
* Authorized users should be able to attach files (Word, Excel, PowerPoint, PDF, etc) to an EP’s Application.
* Authorized users should be able to add a coordinator to an EP.
* An applicant should have a checklist of recommended and required steps for completing the application process.
* An EP member should have a checklist of recommended and required steps for meeting requirements.

### Starting an application
* A person unaffiliated with ClinGen needs to be able to start an EP application.
* A person affiliated with ClinGen needs to be able to start an EP application.
* A OC member should be notified of a new application being started.

### EP Definition
* A coordinator needs to add members to their expert panel
  * An EP member should have the following data:
    * First and last name
    * Email address
    * Institution
    * Area and type of expertise
    * Role (i.e. Biocurator, Coordinator, Leadership, etc.)
  * Added members should be notified that they have been added and given information about how to log in
  * EP members must be able to complete a COI form.
    * EP members should be reminded to complete their COI form 2 weeks after they have been added to an EP if it is not already completed
* A coordinator should be able to update EP members
* A coordinator should be able to delete EP members
* A coordinator should be able to define the scope of the EP including
  * disease focus
  * gene/variant list
* A coordinator needs information about how to complete the application
  * A coordinator would like "gold-standard" examples of an application
* GCEP (also VCEPs?) leadership members must be able to submit an attestation to the OC including the following:
  * This group will utilize the ClinGen Gene Curation Interface for documentation of all gene-disease validity classifications.
  * All curations completed by this group are expected to be made publicly available through the ClinGen website upon completion.
  * The ClinGen publication policy should be reviewed and a manuscript concept sheet should be submitted to the NHGRI and ClinGen Steering Committee before the group prepares a publication for submission.
  * Draft manuscripts must be submitted to the ClinGen Gene Curation WG via Laura Milko for review prior to submission.
* System should notify users about unmet membership recommendations before submision
  * Representation of expertise (clinical,diagnostic laboratory, and basic research).
  * recommended \# of members (10+)
  * \# of biocurators (4 full time)
  * Leadership diversity (i.e. at least 3 institutions represented)
* Coordinator should be able to determine EP name based on (ClinGen protocol)[https://docs.google.com/spreadsheets/d/13ONs7_Dl0U_UyvVr0tgwr_Mk_nNgNUPoIWbhSgJLBNA/edit#gid=0]
  * Full Base EP name
  * Short Base EP name
  * System should generate names based on base name:
    * Affiliation name for (GCI/VCI and Evidence Repo)
    * Website Long and Short
    * ClinVar submission and publication name
    * AMCG/AMP specifications name
* A coordinator needs to be able to define the scope of the EP
  * Description of scope
  * Gene List
* A coordinator needs to be able to define an SOP 
* An authorized user should be able to comment on and SOP

### AMCG/AMP Guideline Specifications
* Baylor’s Specification registry will handle structured entry and versioning of ACMG guidelines specifications.
* Applicants and users should be able to move easily between this system and the Specifications Registry.
* ~~A coordinator should be able to create, update, and delete draft AMCG guidelines in a structured way~~
  * ~~ACMG/AMP guidelines have the following structure:~~
     * ~~applicable genes~~
     * ~~Description for criteria~~
        * ~~PVS1, PS1-4, PM1-6, PP1-5, BA1, BS1-4, BP1-6~~
    * ~~Rules for pathogenic and benign classification~~
* ~~An authorized user should be able to comment on guidelines draft.~~
* ~~Comments should be related to a specific draft of a guidelines section~~
* ~~A coordinator must be able to submit draft guidelines to the SVI RSRC.~~

### Specifications Pilot
* The system should be able to collection GCI/VCI records related to pilot.
* A coordinator must be able to submit final specifications and variants used in pilot to SVI RSRC
* A SVI RSRC member should be able to provide feedback on specifications and pilot
* A VCEP member should be able to reply to SVI RSRC feedback.
* A SVI RSRC member should be able to approve final specifications

### Final approval
* A coordinator must select one of the approved Standard Review Process: 
  1. *Biorcurator review followed by VCEP discussion* 
  1. "Paired biocurator/expert review followed by expedited VCEP approval"
* A coordinator must complete the *Reanalysis and Discrepency Resolution Schedule* attestation
* A coordinator must submit 5 or more written evidence summaries.
* A coordinator must list designated biocurators and attest that they have completed level 1 and 2 training.
* A coordinator must list EP members who will be *Biocurator Trainors*
* A coordinator must list EP members who will be *Core Approval Members*
* A coordinator must attest to *NHGRI Data Availability* statements

## Expert panel maintainence
* A coordinator should be able to see a list of EP members including the following information:
  * Name, email, COI status, role
* A coordinator should be able to add publications produced by the EP.
  * publication record should include at least the title, authors, and PMID.
* A coordinator should be able to indicate that the EP would like to expand it's scope of work to inclue new genes.
* A coordinator should be able to submit AMCG/AMP Specifications for new genes added to the EP's scope of work.
* Approval of Specifications for genes added to the EPs scope of work should go through *AMCG/AMP Guildine Specifications*, *Specifications Pilot*, and *Final Approval*

### Annual Review
* A coordinator must review EP members and make any updates that have not been recorded.
* A coordinator must complete any new application sections that they have not previously completed.

### Comments
* An authorized user should be able to reply to a comment on any part of an application.

## Integration and Data Sharing
* Authorized external systems should be able to request information about an expert panel including:
  * Application status
  * List of members with name, email, and role
  * Associated CDWG
* EP events should be produced sent to the Clingen Data Exchange including:
  * Application progress updates
  * EP Member updates (additions, edits, removal)
* Should be able to link GCI/VCI affiliation with EP.
* Should be able to share approved Guidelines w/ Clingen Specification Repository
* Integration with GeneTracker (GCEP/VCEP gene list)
* Groups and members should be synchronized with the Personnel Management system

  
