
    <template>
  <div class="centered-container">

    <h1>ClinGen Demographic Survey</h1>
    <!-- Background and Purpose section -->
    <section>
      <h2>Background and Purpose</h2>
      <p>

        <b>Why are we asking for this information?</b> The Clinical Genome Resource values the diversity of our
        participants and works to maintain a culture of acceptance, accessibility, and diversity : (see our
        <a href="https://clinicalgenome.org/working-groups/jedi-advisory-board/" target="_blank">Justice, Equity,
          Diversity, and Inclusion (JEDI) Action Plan</a> ) Providing
        this information will help us to better understand our ClinGen community, focus current efforts to enhance
        diversity, and identify areas for future outreach. We sincerely appreciate your time, attention, and willingness
        to help.
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
        <form @submit.prevent="editSurvey">
        <h2>Participant Information on Country</h2>

        <p>The current list of countries comes from (<a href="https://www.iso.org/iso-3166-country-codes.html"
            target="_blank">the international standard ISO 3166 country codes</a>). We recognize that this
          list may not be complete or satisfy all, so please feel free to choose “other” and provide a free text
          response.
        </p>

        <div>

        </div>
        <!-- Country of Birth Dropdown -->
        <div>
          <label for="birth_country">Country of Birth: </label>
          <select id="birth_country" name="birth_country" v-model="formdata.data.birth_country">
            <option value="">Select country</option>
            <option v-for="country in countries" :key="country.value" :value="country.value">{{ country.label }}
            </option>
          </select>

        </div>
        <div>
          <label for="country-state">If you currently live in the United States, what is your state/territory of
            residence?</label>
          <select id="country-state" name="country-state" v-model="formdata.data.state">
            <option value="">Select state</option>
            <option v-for="state in states" :key="state.value" :value="state.value">{{ state.label }}</option>
          </select>
        </div>

        <br>
        <h2>Participant Information on Age</h2>
        <br>
        <div style="display: flex; align-items: center;">
          <label>What year were you born?</label>
          <input class="w3-input" type="text" id="birth_year" v-model="formdata.data.birth_year" required>
        </div>


       
        <!-- Submission Button -->
        <button type="button" @click="editSurvey(uuid)">Submit Demographic Survey</button>



    </form>

    </section>
  </div>

</template>

   
<script>
//import Person from '@/domain/person'
import DemographicsForm from '@/components/people/DemographicsFormOptionsFinal.vue'
const baseUrl = '/api/people';
import { useRouter } from 'vue-router';

import axios from 'axios';
import { mapGetters } from 'vuex';
import { useStore } from 'vuex';
let store = useStore();
import Person from '@/domain/person';
console.log(Person);
import isValidationError from '@/http/is_validation_error';

var items = []

export default {
    name: 'DemographicsProfile',
    components: {
        DemographicsForm
       
    },
    props: {
        person: {
            type: Person,
          //  required: true,
        },
      uuid: {
      required: true,
      default: null,
      // return this.$store.getters('people/currentItem', {uuid: this.uuid});
      type: String
    },
    },
    data () {
        return {
            
                formdata:  {
                    data: {
          birth_country: null, // Ensure the nested structure is initialized
          birth_year: null,
          birth_country_opt_out: false,
      reside_country_opt_out: false,
      state: null,
      id: null,
        }
                 //   birth_country: null,
                //    id: null,
                //    email: null,
                //   first_name: null,
                 //   last_name: null,
                //    country_id: null,
                //    timezone: null,
                },
             
            //},
            showEditForm: false,
         //   selectedCountry: null,
        
      reside_country: null,
      //birth_country: null,
     // birth_year: null,
     // reside_state: null,
      optOutEthnicity: false,
      reside_state_opt_out: false,
      selected_specialty: null,
      selected_support: [],
      grant_detail: null,
      support_opt_out: false,
      support_other: null,
      specialty: null,
      institution_id: null,
      gender: [],
      disadvantaged: null,
      birth_country_other: null,
      reside_country_other: null,
      gender_identities_other: null,
      gender_preferred_term: null,
      occupations_other: null,
      user: null,
      error: null,
      errors: {},
      profile: {},
      saving: false,
     // countries: [],
      items: [],

      // TODO: get from database via store
      states: [
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
      ],

      selected_reside_state: [],

        }
    },
    computed: {
        formDialogTitle () {
            const name = (this.userIsPerson(this.person)) ? 'your' : `${this.person.name}'s`;
            return `Edit ${name} information.`
        },

        countries() {
      return this.$store.getters['countries/items'].map(i => ({ value: i.id, label: i.name }));
      //return this.$store.state.countries.items; // Assuming the countries data is stored in state
    },

        selectedCountry: {
    get() {
        //console_log(this.countries[this.formdata.data.birth_country]?.value);
      return this.countries[this.formdata.data.birth_country]?.value;
    },
    set(value) {
      this.formdata.data.birth_country = this.countries.findIndex(country => country.value === value);

     // const index = this.countries.findIndex(country => country.value === value);
    //  this.formdata.data.birth_country = index;
    }
  }
        
    },

   async created()
    {
       // comsole.log((${baseUrl}/${uuid}) )
      //  console.log(this.uuid);
        await this.getUser(this.uuid); // Replace 'user-uuid' with the actual UUID
    
    // birth_country: this.user.data.birth_country;
    // Optionally, load countries data here or elsewhere in your component
  },


    methods: {
        editPerson () {
            this.$store.commit('people/setCurrentItemIndex', this.person);
            this.showEditForm = true;
        },
        hideEditForm () {
            this.showEditForm = false;
        },

        async getUser(uuid) {
      try {
        console.log(`${baseUrl}/${uuid}`);
        console.log('baseUrl:', baseUrl); // Check the base URL
        console.log('uuid:', uuid); // Check the UUID
        const response = await axios.get(`${baseUrl}/${uuid}`); // Assuming 'baseUrl' is defined
        this.formdata = response.data;
        console.log(response.data);
       // console.log(this.user); // Access user data within the component
        console.log(this.formdata.data.email);
        console.log(this.formdata.data.birth_country);

        // Convert the index to the country value
    if (this.formdata.data.birth_country !== undefined && this.formdata.data.birth_country !== null) {
      const countryIndex = this.formdata.data.birth_country;
      this.formdata.data.birth_country = this.countries[countryIndex]?.value;
    }
        //birth_country = this.user.data.birth_country;
      } catch (error) {
        this.error = error; // You might want an 'error' data property
      }
    },

    async fetchCountries() {
      try {
        await this.$store.dispatch('countries/getItems');
      } catch (error) {
        console.error('Error fetching countries:', error);
      }
    },

    async editSurvey(uuid) {
      // ... (Previous implementation )

      // Use 'this.user' to access data inside addSurvey. For example:
      // console.log("identities:", this.identities);
      //  console.log("gender:", this.gender);

      items = {
        birth_country: this.formdata.data.birth_country,
       // reside_country: this.reside_country,
       state: this.formdata.data.state,
       // state_opt_out: this.reside_state_opt_out,
       birth_year: this.formdata.data.birth_year,
       // birth_country_opt_out: this.birth_country_opt_out,
      //  reside_country_opt_out: this.reside_country_opt_out,
       // reside_state_opt_out: this.reside_state_opt_out,
      //  identities: this.selected_identities,
       // gender_identities: this.selected_gender_identities,
       // ethicities: this.selected_ethnicities,
       // optOutEthnicity: this.optOutEthnicity,
       // occupations: this.selected_occupations,
       // specialty: this.selected_specialty,
        id: this.formdata.data.id,
        email: this.formdata.data.email,
        institution_id: this.formdata.data.institution_id,
       // gender: this.selected_gender_identities,
       // support: this.selected_support,
      //  grant_detail: this.grant_detail,
       // support_opt_out: this.formdata.data.support_opt_out,
       // support_other: this.formdata.data.support_other,
        // reside_state: this.reside_state,
        // institution_id: this.user.institution_id,
        // primary_occupation_id: user.value.data.primary_occupation_id,
        first_name: this.formdata.data.first_name,
        last_name: this.formdata.data.last_name,
        country_id: this.formdata.data.country_id,
        timezone: this.formdata.data.timezone,
      //  disadvantaged: this.disadvantaged,
        // ... more fields from this.user
      };
      console.log(items);

      try {
        const response = await axios.put(`${baseUrl}/${this.uuid}/demographics`, items);
        //const response = await axios.put(`${baseUrl}/${this.uuid}/demographics`, items);
        console.log(response.data);
        //router.push({ name: 'Dashboard' }); // Redirect on success
       // this.$router.push({ name: 'Dashboard' });
       this.$emit('saved');
       //this.$router.push({ name: 'Dashboard' }); 
       // console.log('Redirection attempted'); // Did you reach this line?
      } catch (error) {
        console.error("Error updating user:", error);
      }

    },

    mounted() {

//Promise.all([this.getUser(this.uuid), [this.fetchCountries()])
Promise.all([this.fetchCountries()])

  // const button = this.$el.querySelector('button');
  //if (button) {
  //  button.addEventListener('click', () => this.addSurvey(this.uuid));
  //}
  //.then(() => {
  //   Now you're guaranteed that both getUser and fetchCountries have finished

  //  const button = this.$el.querySelector('button');
  // if (button) {
  //   button.addEventListener('click', () => this.addSurvey(this.uuid));
  // }

  //  ... rest of your mounted code 
  // })
  .catch(error => {
    //     Handle errors from getUser or fetchCountries
    console.error("Error in mounted:", error);
  });
}

    }
}
</script>