<template>
    <form :key="formKey" :uuid="person.uuid">

        <div class="centered-container">

            <h1>ClinGen Demographic Information</h1>
            <!-- Background and Purpose section -->

            <div v-show="!editModeActive" type="button" class="pt-4 border-t mt-4" style="text-align: center;">
                <button class="btn btn-sm" @click="editPerson($event)">
                    Edit Info
                </button>
            </div>

            <section>
                <h2>Background and Purpose</h2>
                <p>

                    <b>Why are we asking for this information?</b> The Clinical Genome Resource values the diversity of
                    our
                    participants and works to maintain a culture of acceptance, accessibility, and diversity: (see our
                    <a href="https://clinicalgenome.org/working-groups/jedi-advisory-board/" target="_blank">Justice,
                        Equity,
                        Diversity, and Inclusion (JEDI) Action Plan</a> ) Providing
                    this information will help us to better understand our ClinGen community, focus current efforts to
                    enhance
                    diversity, and identify areas for future outreach. We sincerely appreciate your time, attention, and
                    willingness
                    to help.
                </p>
                <p><b>Who can access my data?</b> Only a small number of ClinGen staff members will have access to the
                    demographic
                    data. We will not share any individual data. Data may be shared/presented in aggregate.</p>

                <p><b>What do we do with this data?</b> This is entirely for internal and informational use to
                    understand
                    our
                    participants. For example, we might use this information to focus efforts on engaging early or
                    mid-career
                    scientists, or to develop materials for enhanced learning accessibility based on participant
                    feedback.</p>

                <p> Please note: Select 'Prefer not to answer' for any questions you do not wish to answer</p>

                <!-- Additional paragraphs and sections -->
            </section>
            <!-- Participant Information section -->
            <section>
                <form @submit.prevent="addSurvey">
                    <h2>Participant Information on Country</h2>

                    <p>The current list of countries comes from <a
                            href="https://www.iso.org/iso-3166-country-codes.html" target="_blank"> the international
                            standard ISO 3166 country codes</a>. We recognize that this
                        list may not be complete or satisfy all, so please feel free to choose “other” and provide a
                        free text
                        response. Please choose only one option from the dropdown list, other text, or prefer not to
                        answer.
                    </p>

                    <div>

                    </div>
                    <!-- Country of Birth Dropdown -->
                    <div>
                        <label for="birth_country">Country of Birth: </label>
                        <select id="birth_country" name="birth_country" v-model="formdata.birth_country"
                            v-bind:disabled="!editModeActive">
                            <option value="">Select country</option>
                            <option v-for="country in countries" :key="country.value" :value="country.value">{{
        country.label }}
                            </option>
                        </select>

                    </div>

                    <div style="display: flex;">
                        <label>Other: </label>
                        <input class="w3-input" type="text" id="birth_country_other"
                            v-model="formdata.birth_country_other" v-bind:disabled="!editModeActive">

                    </div>

                    <div style="display: flex;" v-if="formDataLoaded">
                        <input type="checkbox" id="birth_country_opt_out" v-model="formdata.birth_country_opt_out"
                            v-bind:disabled="!editModeActive">

                        <label for="birth_country_opt_out"> Prefer not to answer</label>

                    </div>
                    <br>
                    <div>
                        <!-- Country of Residence Dropdown -->
                        <label for="reside_country">Country of Residence: </label>

                        <select id="reside_country" name="reside_country" v-model="formdata.reside_country"
                            v-bind:disabled="!editModeActive">
                            <option value="">Select country</option>
                            <option v-for="country in countries" :key="country.value" :value="country.value">{{
        country.label }}
                            </option>
                        </select>
                    </div>

                    <div style="display: flex;">

                        <label>Other: </label>
                        <input class="w3-input" type="text" id="reside_country_other"
                            v-model="formdata.reside_country_other" :disabled="!editModeActive">
                    </div>

                    <div style="display: flex;">
                        <input type="checkbox" id="reside_country_opt_out" v-model="formdata.reside_country_opt_out"
                            v-bind:disabled="!editModeActive">

                        <label for="reside_country_opt_out">Prefer not to answer </label>

                    </div>
                    <br>

                    <!-- Additional inputs and sections -->
                    <br>

                    <div>
                        <label for="country-state">If you currently live in the United States, what is your
                            state/territory of
                            residence?</label>
                        <select id="country-state" name="country-state" v-model="formdata.reside_state"
                            v-bind:disabled="!editModeActive">
                            <option value="">Select state</option>
                            <option v-for="state in availableStates" :key="state.value" :value="state.value">{{
        state.label }}
                            </option>
                        </select>
                    </div>

                    <div style="display: flex;">
                        <input id="reside_state_opt_out" class="w3-check" type="checkbox"
                            v-model="formdata.reside_state_opt_out" v-bind:disabled="!editModeActive">
                        <label>Prefer not to answer </label>
                    </div>


                    <br>
                    <h2>Participant Information on Race/Ethnicity</h2>

                    <p>ClinGen Participant Diversity: We ask the following question for aggregate informational purposes
                        to
                        understand
                        ClinGen participant diversity. The free-text response can be used in place or in addition to the
                        listed
                        categories. </p>

                    <p> The options below come from updates by the <a href="https://www.census.gov/"
                            target="_blank">U.S. 2020
                            Census</a>, and <a
                            href="https://www.researchallofus.org/data-tools/survey-explorer/the-basics-survey/"
                            target="_blank">the All Of Us Basic Survey Questions</a>. We understand that these options
                        do not capture
                        the
                        diversity of racial and ethnic identities in
                        the US, let alone around the world. </p>


                    <legend>Which categories describe you? Select all that apply. Note, you may select more than one
                        group.
                    </legend>



                    <div v-for="ethnicity in availableEthnicities" :key="ethnicity.value" class="flex">
                        <input type="checkbox" :value="ethnicity.value" v-model="formdata.ethnicities"
                            v-bind:disabled="!editModeActive">
                        <label>{{ ethnicity.label }}</label>
                    </div>

                    <div style="display: flex;">
                        <label>Other: </label>
                        <input class="w3-input" type="text" id="ethnicity_other" v-model="formdata.ethnicity_other"
                            v-bind:disabled="!editModeActive">
                    </div>



                    <div style="display: flex;">
                        <input id="optOutEthnicity" class="w3-check" type="checkbox"
                            v-model="formdata.ethnicity_opt_out" v-bind:disabled="!editModeActive">
                        <br>
                        <label>Prefer not to answer</label>

                    </div>
                    <br>
                    <h2>Participant Information on Age</h2>
                    <br>


                    
                    <div>
                        <label for="specialty">Please select the year that you were born.</label>
                        <span style="color: red !important; display: inline; float: none;"></span>
                        <!-- TODO: check with invested parties: should this be multi-select/checkbox? -->
                        <select id="age" name="age" v-model="formdata.birth_year"
                            v-bind:disabled="!editModeActive">
                            <option value="">Select birth year</option>
                            <option v-for="age in availableAges" :key="age"
                                :value="age">
                                {{ age }}
                            </option>
                        </select>
                    </div>


                    <div style="display: flex;">
                        <input id="optOutBirth" class="w3-check" type="checkbox" v-bind:disabled="!editModeActive"
                            v-model="formdata.birth_year_opt_out">
                        <label> Prefer not to answer</label>

                    </div>

                    <br>
                    <h2>Participant Information on Identity</h2>

                    <p>We ask the following questions about identity to understand participation across the ClinGen
                        ecosystem. The
                        framing of the questions around sex and gender reflects recommendations made by <a
                            href="https://nap.nationalacademies.org/read/26424/chapter/1#xi" target="_blank">the
                            National Academies of
                            Sciences, Engineering, and Medicine in 2022. </a> </p>



                    <div class="w3-section">
                        <legend>Which categories describe you? Select all that apply. Note, you may select more than one
                            group.
                        </legend>
                        <div v-for="identity in availableIdentities" :key="identity" style="display: flex;">
                            <input type="checkbox" :value="identity" v-model="formdata.identities"
                                v-bind:disabled="!editModeActive">
                            <label> {{ identity }}
                            </label>
                        </div>
                    </div>



                    <div style="display: flex;">
                        <label>Other: </label>
                        <input class="w3-input" type="text" id="identity_other" v-model="formdata.identity_other"
                            v-bind:disabled="!editModeActive">
                    </div>


                    <div style="display: flex;">
                        <input id="optOutIdentity" class="w3-check" type="checkbox" v-bind:disabled="!editModeActive"
                            v-model="formdata.identity_opt_out">
                        <label> Prefer not to answer</label>

                    </div>



                    <br>
                    <br>

                    <div class="w3-section">
                        <legend>Which categories describe you? Select all that apply. Note, you may select more than one
                            group.
                        </legend>
                        <div v-for="gender_identity in availableGender_Identities" :key="gender_identity"
                            style="display: flex;">
                            <input type="checkbox" :value="gender_identity" v-model="formdata.gender_identities"
                                v-bind:disabled="!editModeActive">
                            <label>{{ gender_identity }}</label>

                        </div>

                        <div class="flex-container">
                            <label>My preferred term is </label>
                            <input class="w3-input" type="text" id="gender_identities_other"
                                v-model="formdata.gender_identities_other" :disabled="!editModeActive">
                        </div>




                        <div style="display: flex;">
                            <input id="optOutGenderIdentities" class="w3-check" type="checkbox"
                                v-bind:disabled="!editModeActive" v-model="formdata.gender_identities_opt_out">
                            <label> Prefer not to answer</label>

                        </div>

                    </div>





                    <br>
                    <h2>ClinGen Background and Experience</h2>

                    <br>



                    <div class="w3-section">
                        <legend>How is your ClinGen work supported? Select all that apply.</legend>


                        <div v-for="support_type in availableSupporttypes" :key="support_type.value" class="flex">
                            <input type="checkbox" :value="support_type.value" v-model="formdata.support"
                                v-bind:disabled="!editModeActive">
                            <label>{{ support_type.label }}</label>
                        </div>

                        <div>
                            <label>Provide more details on source of funding</label>
                            <input class="w3-input" type="text" v-model="formdata.grant_detail"
                                v-bind:disabled="!editModeActive">
                        </div>

                        <label>
                            <input type="checkbox" v-model="formdata.support_opt_out" v-bind:disabled="!editModeActive">
                            Prefer not to answer<br>
                        </label>

                        <div style="display: flex;">
                            <label>Other: </label>
                            <input class="w3-input" type="text" v-model="formdata.support_other"
                                v-bind:disabled="!editModeActive">
                        </div>




                    </div>
                    <br>

                    <h2>Under-Represented Groups and Disadvantaged Backgrounds</h2>

                    <p>ClinGen is invested in expanding access to curated data and participation in Expert
                        Panels/working groups
                        to
                        individuals who may be under-represented or experience disadvantage due to location or life
                        events. We ask
                        these
                        questions to help understand the backgrounds of our participating members.</p>

                    <p>The following text pertains to the disadvantaged background question below. If you are not a
                        US-based
                        participant, please answer the question based on similar criteria in your own country:</p>
                    <br>
                    <p>An individual is considered to be from a disadvantaged background if meeting two or more of the
                        following
                        criteria:<br>

                    <ol type="1">
                        <li>Were or currently are homeless, as defined by the <a
                                href="https://nche.ed.gov/mckinney-vento/" target="_blank">McKinney-Vento Homeless
                                Assistance Act</a>;</li>
                        <li>Were or currently are in the foster care system, as defined by the <a
                                href="https://www.acf.hhs.gov/cb/focus-areas/foster-care" target="_blank">Administration
                                for Children
                                and
                                Families</a>;</li>
                        <li>Were eligible for two or more years in the <a
                                href="https://www.fns.usda.gov/school-meals/income-eligibility-guidelines"
                                target="_blank">Federal Free
                                and
                                Reduced Lunch Program</a>;</li>
                        <li>Have/had no parents or legal guardians who completed a bachelor’s degree <a
                                href="https://nces.ed.gov/pubs2018/2018009.pdf" target="_blank">See</a>;</li>
                        <li>Were or currently are eligible for <a
                                href="https://www2.ed.gov/programs/fpg/eligibility.html" target="_blank">Federal Pell
                                grants</a>;</li>
                        <li>Received support from the as a
                            parent or child <a href="https://www.fns.usda.gov/wic/wic-eligibility-requirements"
                                target="_blank">Special
                                Supplemental Nutrition Program for Women, Infants and Children (WIC)</a></li>

                    </ol>
                    <br>

                    Grew up in one of the following areas: <br>
                    a) a U.S. rural area, as designated by <a href="https://data.hrsa.gov/tools/rural-health"
                        target="_blank">the
                        Health Resources and Services Administration (HRSA) Rural Health
                        Grants Eligibility Analyzer </a>;
                    or<br>

                    b) a Centers for Medicare and Medicaid Services-designated <a
                        href="https://www.qhpcertification.cms.gov/s/LowIncomeandHPSAZipCodeListingPY2020.xlsx?v=1"
                        target="_blank">Low-Income and Health Professional Shortage Areas
                        (qualifying zip codes are included in the file). </a>
                    <br>
                    <br>
                    Only one of these two possibilities can be used as a criterion for the
                    disadvantaged background definition.



                    </p>



                    Based on the NIH definition above, do you consider yourself currently in or having come from a
                    disadvantaged
                    background? Note: If you are not a US-based participant, please answer based on similar criteria in
                    your own
                    country.<br>

                    <div v-for="y_n_unsure_optout in availableY_n_unsure_optout" :key="y_n_unsure_optout.value"
                        class="flex">
                        <input type="radio" :value="y_n_unsure_optout.value" v-model="formdata.disadvantaged"
                            v-bind:disabled="!editModeActive">
                        <label>{{ y_n_unsure_optout.label }}</label>
                    </div>




                    <div style="display: flex;">
                        <label>Optional: Use this free text box to provide any additional detail.</label>
                        <input class="w3-input" id="disadvantaged_other" type="text"
                            v-model="formdata.disadvantaged_other" v-bind:disabled="!editModeActive">
                    </div>

                    <label>
                        <input type="checkbox" v-model="formdata.disadvantaged_opt_out"
                            v-bind:disabled="!editModeActive">
                        Prefer not to answer<br>
                    </label>



                    <br>
                    <h2>Employment</h2>

                    Please choose the option(s) that most accurately describes your role or occupation [select all that
                    apply].<br>
                    <div v-for="occupation in availableOccupations" :key="occupation.value" class="flex">
                        <input type="checkbox" :value="occupation.value" v-model="formdata.occupations"
                            v-bind:disabled="!editModeActive">
                        <label>{{ occupation.label }}</label>
                    </div>

                    <div style="display: flex;">
                        <label>Other: Use this free text box to provide any additional detail. </label>
                        <input id="occupations_other" class="w3-input" type="text"
                            v-model="formdata.occupations_other" v-bind:disabled="!editModeActive">
                    </div>

                    <label>
                        <input type="checkbox" v-model="formdata.occupations_opt_out" v-bind:disabled="!editModeActive">
                        Prefer not to answer<br>
                    </label>


                    <div>
                        <label for="specialty">If you indicated “Medical non-genetics physician”, please select your
                            specialty.</label>
                        <span style="color: red !important; display: inline; float: none;"></span>
                        <!-- TODO: check with invested parties: should this be multi-select/checkbox? -->
                        <select id="specialty" name="specialty" v-model="formdata.specialty"
                            v-bind:disabled="!editModeActive">
                            <option value="">Select specialty</option>
                            <option v-for="specialty in availableNon_genetics_specialties" :key="specialty"
                                :value="specialty">
                                {{ specialty }}
                            </option>
                        </select>
                    </div>

                    <br>


                    <!-- Submission Button -->
                    <button v-show="editModeActive" type="button" @click="addSurvey">Submit Demographic Survey</button>


                </form>

            </section>
        </div>

    </form>
</template>

<script>
const baseUrl = '/api/people';
import { useRouter } from 'vue-router';

import axios from 'axios';
import { mapGetters } from 'vuex';
import { useStore } from 'vuex';
let store = useStore();

import Person from '@/domain/person';
console.log(Person);
import isValidationError from '@/http/is_validation_error';

var items = [];

const ethnicities = [
    { value: 'American Indian', label: 'American Indian or Alaska Native (For example: Aztec, Blackfeet Tribe, Mayan, Navajo Nation, Native Village of Barrow (Utqiagvik) Inupiat Traditional Government, Nome Eskimo Community' },
    { value: 'Asian', label: 'Asian (For example: Asian Indian, Chinese, Filipino, Japanese, Korean, Vietnamese, etc.)' },
    { value: 'Black', label: 'Black, African American, or African (For example: African American, Ethiopian, Haitian, Jamaican, Nigerian, Somali, etc.)' },
    { value: 'Hispanic', label: 'Hispanic, Latino, or Spanish (For example: Colombian, Cuban, Dominican, Mexican or Mexican American, Puerto Rican, Salvadoran, etc.)' },
    { value: 'Middle Eastern', label: 'Middle Eastern or North African (For example: Algerian, Egyptian, Iranian, Lebanese, Moroccan, Syrian, etc.)' },
    { value: 'Pacific', label: 'Native Hawaiian or other Pacific Islander (For example: Chamorro, Fijian, Marshallese, Native Hawaiian, Tongan, etc.)' },
    { value: 'White', label: 'White (For example: English, European, French, German, Irish, Italian, Polish, etc.)' },
]

const occupations = [
    { value: 'genetics physician', label: 'Medical genetics physician' },
    { value: 'non genetics physician', label: 'Medical non-genetics physician' },
    { value: 'pathologist', label: 'Molecular pathologist' },
    { value: 'laboratory geneticist', label: 'Clinical laboratory geneticist' },
    { value: 'genetic counselor', label: 'Genetic counselor' },
    { value: 'clinical trainee', label: 'Clinical resident or fellow' },
    { value: 'basic researcher', label: 'Basic researcher' },
    { value: 'clinical researcher', label: 'Clinical researcher' },
    { value: 'variant analyst', label: 'Variant analyst' },
    { value: 'staff scientist', label: 'Staff scientist' },
    { value: 'bioinformatician', label: 'Bioinformatician' },
    { value: 'biocurator', label: 'Biocurator' },
    { value: 'graduate student', label: 'Graduate student' },
    { value: 'software engineer/developer', label: 'Software engineer/Developer' },
    { value: 'educator', label: 'Educator' },
    { value: 'general geneticist', label: 'General geneticist' },
    { value: 'science policy', label: 'Health care policy or Science policy' },

]

const identities = [
    'Female',
    'Male',
    'Unsure',
    'None',
]

const gender_identities = [
    'Man',
    'Woman',
    'Cisgender',
    'Nonbinary',
    'Transgender',
    'Genderqueer',
    'Agender',
    'Intersex',
    'Unsure'

]

const ages = [
    '2006',
    '2005',
    '2004',
    '2003',
    '2002',
    '2001',
    '2000',
    '1999',
    '1998',
    '1997',
    '1996',
    '1995',
    '1994', '1993', '1992', '1991',
]

const non_genetics_specialties = [
    "Allergy & Immunology",
    "Anesthesiology",
    "Cardiology/Cardiovascular Disease",
    "Child and Adolescent Psychiatry",
    "Colon & Rectal Surgery",
    "Critical Care Medicine",
    "Cytopathology",
    "Dermatology",
    "Emergency Medicine",
    "Endocrinology, Diabetes and Metabolism",
    "Family Medicine",
    "Gastroenterology",
    "General Preventive Medicine and Public Health",
    "Geriatric Medicine",
    "Hematology",
    "Hospice and Palliative Medicine",
    "Infectious Diseases",
    "Internal Medicine",
    "Interventional Cardiology",
    "Medical Genetics and Genomics",
    "Nephrology",
    "Ophthalmology",
    "Neurological Surgery",
    "Neurology",
    "Nuclear Medicine",
    "Obstetrics & Gynecology",
    "Occupational Medicine",
    "Oncology",
    "Orthopaedic Sports Medicine",
    "Orthopaedic Surgery",
    "Otolaryngology",
    "Pain Medicine",
    "Pathology",
    "Pediatric Surgery",
    "Pediatrics",
    "Physical Medicine & Rehabilitation",
    "Plastic Surgery",
    "Preventive Medicine",
    "Psychiatry",
    "Pulmonary Disease and Critical Care Medicine",
    "Radiation Oncology",
    "Radiology",
    "Rheumatology",
    "Sleep Medicine",
    "Surgery - General",
    "Thoracic Surgery",
    "Urology",
    "Vascular Surgery",
]

// TODO: get from database via store
const states = [
    { label: 'ALABAMA', value: 'AL' },
    { label: 'ALASKA', value: 'AK' },
    { label: 'AMERICAN SAMOA', value: 'AS' },
    { label: 'ARIZONA', value: 'AZ' },
    { label: 'ARKANSAS', value: 'AR' },
    { label: 'CALIFORNIA', value: 'CA' },
    { label: 'COLORADO', value: 'CO' },
    { label: 'CONNECTICUT', value: 'CT' },
    { label: 'DELAWARE', value: 'DE' },
    { label: 'DISTRICT OF COLUMBIA', value: 'DC' },
    { label: 'FLORIDA', value: 'FL' },
    { label: 'GEORGIA', value: 'GA' },
    { label: 'GUAM', value: 'GU' },
    { label: 'HAWAII', value: 'HI' },
    { label: 'IDAHO', value: 'ID' },
    { label: 'ILLINOIS', value: 'IL' },
    { label: 'INDIANA', value: 'IN' },
    { label: 'IOWA', value: 'IA' },
    { label: 'KANSAS', value: 'KS' },
    { label: 'KENTUCKY', value: 'KY' },
    { label: 'LOUISIANA', value: 'LA' },
    { label: 'MAINE', value: 'ME' },
    { label: 'MARYLAND', value: 'MD' },
    { label: 'MASSACHUSETTS', value: 'MA' },
    { label: 'MICHIGAN', value: 'MI' },
    { label: 'MINNESOTA', value: 'MN' },
    { label: 'MISSISSIPPI', value: 'MS' },
    { label: 'MISSOURI', value: 'MO' },
    { label: 'MONTANA', value: 'MT' },
    { label: 'NEBRASKA', value: 'NE' },
    { label: 'NEVADA', value: 'NV' },
    { label: 'NEW HAMPSHIRE', value: 'NH' },
    { label: 'NEW JERSEY', value: 'NJ' },
    { label: 'NEW MEXICO', value: 'NM' },
    { label: 'NEW YORK', value: 'NY' },
    { label: 'NORTH CAROLINA', value: 'NC' },
    { label: 'NORTH DAKOTA', value: 'ND' },
    { label: 'NORTHERN MARIANA IS', value: 'MP' },
    { label: 'OHIO', value: 'OH' },
    { label: 'OKLAHOMA', value: 'OK' },
    { label: 'OREGON', value: 'OR' },
    { label: 'PENNSYLVANIA', value: 'PA' },
    { label: 'PUERTO RICO', value: 'PR' },
    { label: 'RHODE ISLAND', value: 'RI' },
    { label: 'SOUTH CAROLINA', value: 'SC' },
    { label: 'SOUTH DAKOTA', value: 'SD' },
    { label: 'TENNESSEE', value: 'TN' },
    { label: 'TEXAS', value: 'TX' },
    { label: 'UTAH', value: 'UT' },
    { label: 'VERMONT', value: 'VT' },
    { label: 'VIRGINIA', value: 'VA' },
    { label: 'VIRGIN ISLANDS', value: 'VI' },
    { label: 'WASHINGTON', value: 'WA' },
    { label: 'WEST VIRGINIA', value: 'WV' },
    { label: 'WISCONSIN', value: 'WI' },
    { label: 'WYOMING', value: 'WY' },
]

const support_types = [
    { value: 'volunteer', label: 'Volunteer outside of work environment' },
    { value: 'grant', label: 'Grants (e.g. NIH, foundational)' },
    { value: 'employer', label: 'Employer supports/allows participation' },
    { value: 'unsure', label: 'Unsure' },
]

const y_n_unsure_optout = [
    { value: 'yes', label: 'Yes' },
    { value: 'no', label: 'No' },
    { value: 'unsure', label: 'Unsure' },

]


export default {
    name: "DemographicsForm",

    emits: [
        'canceled',
        'saved',
        'update'
    ],

    data() {
        return {
            formDataLoaded: false,
            localUuid: this.uuid, // Initialize localUuid either with the prop or as null
            editModeActive: false,
            formdata: {
                birth_country: null,
                birth_country_other: null,
                birth_country_opt_out: false,
                reside_country: null,
                reside_country_other: null,
                reside_country_opt_out: false,
                reside_state: null,
                reside_state_opt_out: false,
                ethnicities: [],
                ethnicity_other: null,
                ethnicity_opt_out: false,
                birth_year: null,
                birth_year_opt_out: false,
                identities: [],
                identity_other: null,
                identity_opt_out: false,
                gender_identities: [],
                gender_identities_other: null,
                gender_identities_opt_out: false,
                support: [],
                grant_detail: null,
                support_opt_out: false,
                support_other: null,
                disadvantaged: null,
                disadvantaged_other: null,
                disadvantaged_opt_out: false,
                occupations: [],
                occupations_other: null,
                occupations_opt_out: false,
                specialty: [],
            },

            //data variables other than those selected(checkbox) or retrieved from database
            error: null,
            errors: {},
        };
    },

    props: {
        uuid: {
            required: false,
            default: null,
            type: String
        },

        startInEditMode: {
            type: Boolean,
            required: false,
            default: false
        }


    },


    computed: {
        ...mapGetters({
            person: 'people/currentItem',

        }),



        countries() {
            return this.$store.getters['countries/items'].map(i => ({ value: i.id, label: i.name }));

        },

        formKey() {
            // Change key when UUID changes to force re-render
            return `form-${this.uuid}`;
        },


        availableEthnicities() {
            return ethnicities;
        },

        availableAges() {
            return ages;
        },

        availableIdentities() {
            return identities;
        },

        availableGender_Identities() {
            return gender_identities;
        },

        availableOccupations() {
            return occupations;
        },

        availableNon_genetics_specialties() {
            return non_genetics_specialties;
        },

        availableY_n_unsure_optout() {
            return y_n_unsure_optout;
        },

        availableStates() {
            return states;
        },

        availableSupporttypes() {
            return support_types;
        },

        disableField() {
            return !this.isNew && !this.editform;
        },

        disableCountryDropdown() {
            return this.formdata.data.birth_country_other !== '' || this.selected_birth_country_opt_out;
        },
        disableOtherInput() {
            return this.formdata.data.birth_country !== '' || this.selected_birth_country_opt_out;
        },
        disableOptOutCheckbox() {
            return this.formdata.data.birth_country !== '' || this.formdata.data.birth_country_other !== '';
        },

        //calculate the current date and format it
        formattedDate() {
            const originalDate = new Date().toLocaleDateString();
            const [month, day, year] = originalDate.split('/');

            // Construct a new date object from the components
            const date = new Date(year, month - 1, day); // Month is 0-indexed in Date

            // Format the date to 'YYYY-MM-DD'
            return date.toISOString().split('T')[0];

        },

       // isSection1Valid() {
        //    return
        //    (typeof birth_country_other === 'string' && birth_country_other.trim() !== '') ||
         //       (typeof birth_country === 'string' && birth_country.trim() !== '') ||
         //       birth_country_opt_out === true
            // Section 1 is valid if there's text input or any checkbox is selected
           // return this.formdata.birth_country_other !== '' || this.formdata.birth_country.length > 0 || this.formdata.birth_country_opt_out;
       // },

      //  isSection1Valid() {
            // Ensure formdata and its properties are defined
       //     if (!this.formdata) return false;
        //    console.log(this.formdata.birth_country);
        //    console.log(this.formdata.birth_country_other);
       //     console.log(typeof this.formdata.birth_country);

           // const { birth_country_other, birth_country, birth_country_opt_out } = this.formdata;

            // Ensure birth_country_other is a string and trimmed, birth_country has content, or opt-out is true
      //      return (
        //       (typeof this.formdata.birth_country_other === 'string' && this.formdata.birth_country_other.trim() !== '') ||
              //  (this.formdata.birth_country !== null) ||
         //     (this.formdata.birth_country !== null && this.formdata.birth_country !== 0) ||
         //       this.formdata.birth_country_opt_out === true
          //  );
       // },

        isAgeSectionValid() {

           //  const { birth_year1, birth_year_opt_out } = this.formdata;

            return (
               // (typeof this.formdata.birth_year === 'string' && this.formdata.birth_year.trim() !== '') ||
              //(this.formdata.birth_year.trim() !== '') ||
               //  (this.formdata.birth_country !== null) ||
              //(this.formdata.birth_country !== null && this.formdata.birth_country !== 0) ||
                this.birth_year_opt_out === true
            );
        },

       // isFormValid() {
            // The entire form is valid if all sections are valid
            //return this.isSection1Valid;
       //     return this.isAgeSectionValid;

       // }

    },

    methods: {


        async fetchCountries() {
            try {
                await this.$store.dispatch('countries/getItems');
            } catch (error) {
                console.error('Error fetching countries:', error);
            }
        },


        async getUser(localuuid) {
            try {
                const response = await axios.get(`${baseUrl}/${this.localUuid}`); // Assuming 'baseUrl' is defined
                // TODO: might be better off picking just the data property (and maybe just its demographics-related values)
                this.formdata = response.data.data;
                this.formDataLoaded = true;

            } catch (error) {
                this.error = error; // You might want an 'error' data property
            }
        },


        //  async handleSave() {
        //     await store.dispatch('forceGetCurrentUser');

        //     router.replace(props.redirectTo)
        // },

        // TODO uuid is never sent to this function as a parameter...
        async addSurvey(uuid) {
            this.checkValidity();
          //  console.log(items);
            if (this.isFormValid) {
                alert('Form is valid and ready to be submitted!');
                items = {
                    ...this.formdata,
                    // TODO: should this be dateTime instead?
                    demographics_completed_date: this.formattedDate,
                    demographics_version: 1,
                };
                console.log(items);

                try {
                    const response = await axios.put(`${baseUrl}/${this.localUuid}/demographics`, items);
                    console.log(response.data);
                    if (response.status === 200)
                    {
                        alert('Form was submitted succesfully!');
                    }
                        
                    
                    this.$emit('saved');

                } catch (error) {
                    if (error.response) {
                        // Request made and server responded

                        if (error.response.status === 404) {
                            this.error = 'Resource not found';
                        } else if (error.response.status === 500) {
                            this.error = 'Server error';
                        }
                    } else if (error.request) {
                        // The request was made but no response was received
                        console.log(error.request);
                        this.error = 'Network error';
                    } else {
                        // Something happened in setting up the request that triggered an Error
                        console.log('Error', error.message);
                        this.error = 'Error in request setup';
                    }

                    console.error("Error updating user:", error);
                }

                this.editModeActive = false;

            } else {
                // Notify user to fill all required sections
                alert('Please fill in all required sections.');
            }
            // }


        },

        isSection1Valid() {
            // Ensure formdata and its properties are defined
            if (!this.formdata) return false;

            const { birth_country_other, birth_country, birth_country_opt_out } = this.formdata;

            // Ensure birth_country_other is a string and trimmed, birth_country has a valid index, or opt-out is true
            return (
                (typeof birth_country_other === 'string' && birth_country_other.trim() !== '') ||
                (typeof birth_country === 'string' && birth_country.trim() !== '' && birth_country !== '0') ||
                birth_country_opt_out === true
            );
        },

        checkValidity() {
        if (this.isSection1Valid()) {
            console.log("Section 1 is valid");
            this.isFormValid = true;
        } else {
            console.log("Section 1 is not valid");
        }

      //  if (this.isAgeSectionValid()) {
       //     console.log("Age section is valid");
       // } else {
       //     console.log("Age section is not valid");
        //}
    },

        editPerson(event) {
            event.preventDefault();
            this.editModeActive = true;
        },



    },



    mounted() {
        this.editModeActive = this.startInEditMode;
        // TODO: not sure why there is an extra level here...
        {
            if (!this.uuid) {
                // Check if uuid prop is not passed
                const pathSegments = window.location.pathname.split('/');
                const uuidIndex = pathSegments.findIndex(segment => segment === 'people') + 1;

                // Ensure uuidIndex is within the array bounds
                if (uuidIndex > 0 && uuidIndex < pathSegments.length) {
                    this.localUuid = pathSegments[uuidIndex];  // Extract from the URL and assign to local data property
                } else {
                    console.error('UUID segment not found after "people" in the URL.');
                    this.localUuid = null; // or handle as appropriate
                }

                console.log(this.localUuid);
            } else {
                this.localUuid = this.uuid; // Corrected to match case sensitivity
            }



            Promise.all([this.getUser(this.localUuid), this.fetchCountries()]).then(() => {
                console.log('Data fetched successfully');

            }).catch(error => {
                if (error.response) {

                    if (error.response.status === 404) {
                        this.error = 'Resource not found';
                    } else if (error.response.status === 500) {
                        this.error = 'Server error';
                    }
                } else if (error.request) {
                    // The request was made but no response was received
                    console.log(error.request);
                    this.error = 'Network error';
                } else {
                    // Something happened in setting up the request that triggered an Error
                    console.log('Error', error.message);
                    this.error = 'Error in request setup';
                }
                console.error('Error fetching data', error);
            });


        }


    }



}








</script>

<style scoped>
/* Base styles */
body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    background-color: #f4f4f4;
    color: #333;
}

a {
    color: #007BFF;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}

h1,
h2,
h3 {
    margin: 0;
    padding: 0;
}

/* Container styling */
body>h1 {
    background-color: #333;
    color: #fff;
    text-align: center;
    padding: 20px 0;
}

section {
    margin: 20px 3%;
    background-color: #fff;
    padding: 20px;
    box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
}

/* Form styling */
form {
    margin: 20px 3%;
    background-color: #fff;
    padding: 20px;
    box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
}


input {
    margin: 0.4rem;
}

span {
    color: red;
}

.form-control {
    width: 100%;
    padding: 10px;
    margin: 5px 0;
    box-sizing: border-box;
}

.w3-input {
    width: 100%;
    padding: 10px;
    margin: 5px 0;
    box-sizing: border-box;
    border: 1px solid #ddd;
}

.w3-check {
    margin-right: 5px;
}

/* Clearfix for floating elements */
.clearfix::after {
    content: "";
    clear: both;
    display: table;
}


.checkbox-margin {
    margin-right: 200px;
    /* Adjust the space as needed */
}


button {

    padding: 15px 32px;
    /* Padding around the text */
    text-align: center;
    /* Center the text inside the button */
    text-decoration: none;
    /* Remove underlines from any text */
    display: inline-block;
    /* Align the button next to other elements */
    font-size: 16px;
    /* Set the font size */
    margin: 4px 2px;
    /* Spacing around the button */
    cursor: pointer;
    /* Change mouse pointer to indicate clickable */

    border-radius: 8px;
    /* Rounded corners */
    box-shadow: 0 4px #999;
    /* Shadow effect for depth */

    background-color: white;
    /* White background */
    color: black;
    /* Black text color */
    border: 1px solid black;
    /* Add a solid black border */
}

#birth_country {
    /* Target the select element */
    font-size: 16px;
    /*  Adjust as needed */
    padding: 8px;
    /* Add padding for visual space */
    background-color: #fff;
    /* White background for contrast */
    border: 1px solid #ccc;
    /* Subtle border */
}

#birth_country option {
    background-color: #fff;
    /* Ensure options are also visible */
}

input[type="checkbox"]+label:before {
    content: "";
    display: inline-block;
    padding-right: .2cm;
}

input[type="radio"] {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
}


input[type="radio"] {
    appearance: none;
    background-color: #fff;
    border: 2px solid #000;
    border-radius: 50%;
    width: 20px;
    height: 20px;
}

input[type="radio"]:checked {
    background-color: #0044cc;
}

.flex-container {
    display: flex;
    align-items: center;
    /* Aligns items vertically in the center */
}

.flex-container label {
    flex: 0 1 auto;
    /* Do not grow, allow shrink, and base width on content */
    white-space: nowrap;
    /* Prevents the label from wrapping */
    margin-right: 10px;
    /* Adds some space between the label and the input */
}

.flex-container input {
    flex: 1 1 auto;
    /* Allow grow and shrink, and base width on remaining space */
}
</style>
