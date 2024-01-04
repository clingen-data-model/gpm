<template>
  <div class="centered-container">
    <h1>ClinGen Demographic Survey</h1>
    <!-- Background and Purpose section -->
    <section>
      <h2>Background and Purpose</h2>
      <p>
        <b>Why are we asking for this information?</b> The Clinical Genome Resource values the diversity of our
        participants and works to maintain a culture of acceptance, accessibility, and diversity (see our
        <a href="https://clinicalgenome.org/working-groups/jedi-advisory-board/" target="_blank">Justice, Equity,
          Diversity, and Inclusion (JEDI) Action Plan</a>). Providing this information will help us to better understand
        our ClinGen community, focus current efforts to enhance diversity, and identify areas for future outreach. We
        sincerely appreciate your time, attention, and willingness to help.
      </p>
      <p><b>Who can access my data?</b> Only a small number of ClinGen staff members will have access to the demographic
        data. We will not share any individual data. Data may be shared/presented in aggregate.</p>

      <p><b>What do we do with this data?</b>This is entirely for internal and informational use to understand our
        participants. For example, we might use this information to focus efforts on engaging early or mid-career
        scientists, or to develop materials for enhanced learning accessibility based on participant feedback.</p>

      <p> Please note: Select 'Prefer not to answer' for any questions you do not wish to answer</p>

      <!-- Additional paragraphs and sections -->
    </section>
    <!-- Participant Information section -->
    <section>
      <h2>Participant Information on Country</h2>

      <p>The current list of countries comes from the international standard <a
          href="https://www.iso.org/iso-3166-country-codes.html" target="_blank">ISO 3166 country codes</a>. We recognize
        that this list may not be complete or satisfy all, so please feel free to choose “other” and provide a free text
        response.
      </p>
      <div>

      </div>
      <!-- Country of Birth Dropdown -->
      <div>
        <label for="birth_country">Country of Birth: </label>
        <select id="birth_country" name="birth_country" v-model="demographics.birth_country">
          <option value="">Select country</option>
          <option v-for="country in countries" :key="country.value" :value="country.value">{{ country.label }}</option>
        </select>
      </div>

      <div style="display: flex;">
        <label>Other: </label>
        <input class="w3-input" type="text">
      </div>

      <div style="display: flex;">
        <input type="checkbox" class="checkbox-margin" id="birth_country_opt_out"
          v-model="demographics.birth_country_opt_out">
        <label for="birth_country_opt_out"> Prefer not to answer</label>

      </div>
      <br>
      <div>
        <!-- Country of Residence Dropdown -->
        <label for="reside_country">Country of Residence: </label>
        <select id="reside_country" name="reside_country" v-model="demographics.reside_country">

          <option value="">Select country</option>
          <option v-for="country in countries" :key="country.value" :value="country.value">{{ country.label }}</option>
        </select>
      </div>

      <div style="display: flex;">

        <label>Other: </label>
        <input class="w3-input" type="text">
      </div>
      <div style="display: flex;">
        <input type="checkbox" id="reside_country_opt_out" v-model="demographics.reside_country_opt_out">
        <label for="reside_country_opt_out">Prefer not to answer </label>

      </div>
      <br>

      <!-- Additional inputs and sections -->
      <br>
      <!-- 226 is United States -->
      <div v-if="demographics.reside_country == 226">
        <div>
          <label for="country-state">If you currently live in the United States, what is your state/territory of
            residence?</label>
          <select id="country-state" name="country-state" v-model="demographics.reside_state">
            <option value="">Select state</option>
            <option v-for="state in states" :key="state.value" :value="state.value">{{ state.label }}</option>
          </select>
        </div>
        <div style="display: flex;">
          <input id="reside_state_opt_out" class="w3-check" type="checkbox" v-model="demographics.reside_state_opt_out">
          <label>Prefer not to answer </label>
        </div>
      </div>
      <br>
      <h2>Participant Information on Race/Ethnicity</h2>

      <p>ClinGen Participant Diversity: We ask the following question for aggregate informational purposes to understand
        ClinGen participant diversity. The free-text response can be used in place or in addition to the listed
        categories.</p>

      <p>
        The options below come from updates by the <a href="https://www.census.gov/" target="_blank">U.S. 2020 Census</a>,
        and the
        <a href="https://www.researchallofus.org/data-tools/survey-explorer/the-basics-survey/" target="_blank">All Of
          Us Basic Survey Questions</a>.
        We understand that these options do not capture the diversity of racial and ethnic identities in the US, let alone
        around the world.
      </p>

      <legend>Which categories describe you? Select all that apply. Note, you may select more than one group.</legend>

      <div v-for="ethnicity in ethnicities" :key="ethnicity.value" class="flex">
        <input type="checkbox" :value="ethnicity.value" v-model="demographics.ethnicities">
        <label>{{ ethnicity.label }}</label>
      </div>

      <div style="display: flex;">
        <input id="optOutEthnicity" class="w3-check" type="checkbox"><br>
        <label>Prefer not to answer</label>

      </div>
      <br>
      <h2>Participant Information on Age</h2>
      <br>
      <div style="display: flex; align-items: center;">
        <label>What year were you born?</label>
        <input class="w3-input" type="text" id="birth_year" v-model="demographics.birth_year" required>
      </div>


      <div style="display: flex;">
        <input id="optOutBirth" class="w3-check" type="checkbox">
        <label> Prefer not to answer</label>

      </div>

      <br>
      <h2>Participant Information on Identity</h2>

      <p>
        We ask the following questions about identity to understand participation across the ClinGen ecosystem. The
        framing of the questions around sex and gender reflects <a
          href="https://nap.nationalacademies.org/read/26424/chapter/1#xi" target="_blank">recommendations made by the
          National Academies of Sciences, Engineering, and Medicine in 2022.</a>
      </p>

      <div class="w3-section">
        <legend>Which categories describe you? Select all that apply. Note, you may select more than one group.</legend>
        <div v-for="gender_identity in gender_identities" :key="gender_identity">
          <label><input type="checkbox" :value="gender_identity" v-model="demographics.gender_identities">
            {{ gender_identity }}
          </label>
        </div>
        <span>Gender identites selected: {{ demographics.gender_identities }}</span>
      </div>

      <div style="display: flex;">
        <label>Other: </label>
        <input class="w3-input" type="text" v-model="demographics.gender_identites_other">
      </div>


      <br>

      <div style="display: flex;">
        <label>My preferred term is </label>
        <input class="w3-input" type="text" v-model="demographics.gender_preferred_term">
      </div>


      <br>
      <h2>ClinGen Background and Experience</h2>

      <br>
      <div class="w3-section">
        <legend>How is your ClinGen work supported? Select all that apply.</legend>

        <div v-for="support_type in support_types" :key="support_type.value" class="flex">
          <input type="checkbox" :value="support_type.value" v-model="demographics.support">
          <label>{{ support_type.label }}</label>
          <div v-if="support_type.value == 'grant' && demographics.support.indexOf('grant') != -1" style="display: flex;">
            <br>
            <label>Provide more details on source of funding</label>
            <input class="w3-input" type="text" v-model="demographics.grant_detail">
          </div>
        </div>

        <label>
          <input type="checkbox" v-model="demographics.support_opt_out">Prefer not to answer<br>
        </label>

        <div style="display: flex;">
          <label>Other: </label>
          <input class="w3-input" type="text" v-model="demographics.support_other">
        </div>



      </div>
      <br>

      <h2>Under-Represented Groups and Disadvantaged Backgrounds</h2>

      <p>ClinGen is invested in expanding access to curated data and participation in Expert Panels/working groups to
        individuals who may be under-represented or experience disadvantage due to location or life events. We ask these
        questions to help understand the backgrounds of our participating members.</p>

      <p>The following text pertains to the disadvantaged background question below. If you are not a US-based
        participant, please answer the question based on similar criteria in your own country:</p>
      <p>
        An individual is considered to be from a disadvantaged background if meeting two or more of the following
        criteria:
        <!-- TODO: something (tailwind?) is messing with standard ul/ol indentation in an annoying way -->
      <ul class="list-disc">
        <li>
          Were or currently are homeless, as <a href="https://nche.ed.gov/mckinney-vento/" target="_blank">defined by
            the
            McKinney-Vento Homeless Assistance Act</a>;
        </li>
        <li>
          Were or currently are in the foster care system, as <a href="https://www.acf.hhs.gov/cb/focus-areas/foster-care"
            target="_blank">defined by the Administration for
            Children and Families</a>;
        </li>
        <li>
          Were <a href="https://www.fns.usda.gov/school-meals/income-eligibility-guidelines" target="_blank">eligible
            for
            the Federal Free and Reduced Lunch Program</a> for two or more years;
        </li>
        <li>
          Have/had <a href="https://nces.ed.gov/pubs2018/2018009.pdf" target="_blank">no parents or legal guardians who
            completed a bachelor’s degree</a>;
        </li>
        <li>
          Were or currently are <a href="https://www2.ed.gov/programs/fpg/eligibility.html" target="_blank">eligible for
            Federal Pell grants</a>;
        </li>
        <li>
          Received support from the <a href="https://www.fns.usda.gov/wic/wic-eligibility-requirements"
            target="_blank">Special Supplemental Nutrition Program for Women, Infants and Children (WIC)</a> as a parent
          or child;
        </li>
        <li>
          Grew up in one of the following areas:
          <ol class="list-decimal">
            <li>
              a U.S. rural area, as designated by the Health Resources and Services Administration (HRSA) Rural Health
              Grants Eligibility Analyzer (<a href="https://data.hrsa.gov/tools/rural-health"
                target="_blank">Definition</a>); or
            </li>
            <li>
              a <a href="https://www.qhpcertification.cms.gov/s/LowIncomeandHPSAZipCodeListingPY2020.xlsx?v=1"
                target="_blank">Centers for Medicare and Medicaid Services-designated Low-Income and Health Professional
                Shortage Area</a> (qualifying zip codes are included in the linked file).
            </li>
          </ol>

          Only one of these two possibilities can be used as a criterion for the
          disadvantaged background definition.
        </li>
      </ul>
      </p>


      Based on the NIH definition above, do you consider yourself currently in or having come from a disadvantaged
      background? Note: If you are not a US-based participant, please answer based on similar criteria in your own
      country.<br>
      <div v-for="y_n_unsure_optout in y_n_unsure_optout" :key="y_n_unsure_optout.value" class="flex">
        <input type="radio" :value="y_n_unsure_optout.value" v-model="demographics.disadvantaged">
        <label>{{ y_n_unsure_optout.label }}</label>
      </div>

      <div class="flex">
        <label>Optional: Use this free text box to provide any additional detail.</label>
        <input id="disadvantaged-detail" class="w3-input" type="text" v-model="demographics.disadvantaged_detail">
      </div>





      <br>
      <h2>Employment</h2>



      Please choose the option that most accurately describes your role or occupation [select all that apply].<br>
      <div v-for="occupation in occupations" :key="occupation.value" class="flex">
        <input type="checkbox" :value="occupation.value" v-model="demographics.occupations">
        <label>{{ occupation.label }}</label>
      </div>
      <span>Selected occupations: {{ demographics.occupations }}</span>
      <div style="display: flex;">
        <label>Other: Use this free text box to provide any additional detail. </label>
        <input id="occupations_other" class="w3-input" type="text">
      </div>



      <br>


      <div v-if="demographics.occupations.includes('non genetics physician')">
        <label for="specialty">If you indicated “Medical non-genetics physician”, please select your
          specialty.</label><span style="color: red !important; display: inline; float: none;"></span>
        <!-- TODO: check with invested parties: should this be multi-select/checkbox? -->
        <select id="specialty" name="specialty"><br>
          <option value="">Select specialty</option>
          <option v-for="specialty in non_genetics_specialties" :key="specialty" :value="specialty">{{ specialty }}
          </option>
        </select>
      </div>

      <br>
      <br>

      <!-- Submission Button -->
      <button @click="addSurvey">Submit Demographic Survey</button>

    </section>
    <!--     <div v-if="saving" class="mb-2">Saving...</div>
    <button-row v-if="!saving" @submitted="save" @canceled="cancel()" :submit-text="saveButtonText"
      :show-cancel="allowCancel" /> -->
  </div>
</template>
  
<script setup>
import { ref, emits, computed, onMounted } from 'vue'
import { useStore } from 'vuex'
import Person from '@/domain/person'
import isValidationError from '@/http/is_validation_error'

const store = useStore()
const props = defineProps({
  person: {
    type: Object,
    required: true
  },
})

const errors = ref({})
const profile = ref({})
const saving = ref(false)

const reside_country = ref()
// TODO: should fill from profile
const demographics = ref({
  birth_country: null,
  birth_country_other: null,
  birth_country_opt_out: false,
  reside_country: null,
  reside_country_other: null,
  reside_country_opt_out: false,
  reside_state: null,
  reside_state_opt_out: false,
  ethnicities: [],
  gender_identities: [],
  gender_identities_other: null,
  gender_preferred_term: null,
  support: [],
  grant_detail: null,
  support_opt_out: false,
  support_other: null,
  disadvantaged: null,
  disadvantaged_detail: null,
  birth_year: null,
  occupations: [],
  occupations_other: null,
})

const initDemographicsProfile = () => {
  profile.value = { ...props.person.demographics }
}

const save = async () => {
  try {
    saving.value = true
    const updatedPerson = await store.dispatch(
      'people/updateDemographics',
      { uuid: props.person.uuid, attributes: profile.value }
    ).then(rsp => {
      store.dispatch('getCurrentUser', { force: true })
      store.commit('pushSuccess', 'Your profile has been updated.')
      return rsp.data;
    })

    saving.value = false;
    errors.value = {};
    emits('saved', new Person(updatedPerson));
  } catch (error) {
    if (isValidationError(error)) {
      errors.value = error.response.data.errors;
    }
  }
}

const cancel = () => {
  initDemographicsProfile();
  errors.value = {};
  emits('canceled');
};

const countries = computed(() => {
  return store.getters['countries/items'].map(i => ({ value: i.id, label: i.name }))
})

onMounted(() => {
  store.dispatch('countries/getItems');
});

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

const ethnicities = [
  { value: 'American Indian', label: 'American Indian or Alaska Native (For example: Aztec, Blackfeet Tribe, Mayan, Navajo Nation, Native Village of Barrow (Utqiagvik) Inupiat Traditional Government, Nome Eskimo Community' },
  { value: 'Asian', label: 'Asian (For example: Asian Indian, Chinese, Filipino, Japanese, Korean, Vietnamese, etc.)' },
  { value: 'Black', label: 'Black, African American, or African (For example: African American, Ethiopian, Haitian, Jamaican, Nigerian, Somali, etc.)' },
  { value: 'Hispanic', label: 'Hispanic, Latino, or Spanish (For example: Colombian, Cuban, Dominican, Mexican or Mexican American, Puerto Rican, Salvadoran, etc.)' },
  { value: 'Middle Eastern', label: 'Middle Eastern or North African (For example: Algerian, Egyptian, Iranian, Lebanese, Moroccan, Syrian, etc.)' },
  { value: 'Pacific', label: 'Native Hawaiian or other Pacific Islander (For example: Chamorro, Fijian, Marshallese, Native Hawaiian, Tongan, etc.)' },
  { value: 'White', label: 'White (For example: English, European, French, German, Irish, Italian, Polish, etc.)' },
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
  'Unsure',
  'Prefer not to answer',
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
  { value: 'optout', label: 'Prefer not to answer' },
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
  { value: 'variant analyst', label: 'Variant Analyst' },
  { value: 'staff scientist', label: 'Staff Scientist' },
  { value: 'bioinformatician', label: 'Bioinformatician' },
  { value: 'biocurator', label: 'Biocurator' },
  { value: 'graduate student', label: 'Graduate Student' },
  { value: 'software engineer/developer', label: 'Software Engineer/Developer' },
  { value: 'educator', label: 'Educator' },
  { value: 'general geneticist', label: 'General Geneticist' },
  { value: 'science policy', label: 'Health Care Policy or Science Policy' },
  { value: 'no answer', label: 'Prefer not to answer' },
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
}


button {
  padding: 15px 32px;
  text-align: center;
  text-decoration: none;
  /* Remove underlines from any text */
  display: inline-block;
  /* Align the button next to other elements */
  font-size: 16px;
  margin: 4px 2px;
  cursor: pointer;
  /* Change mouse pointer to indicate clickable */

  border-radius: 8px;
  box-shadow: 0 4px #999;

  background-color: white;
  color: black;
  border: 1px solid black;
}
</style>