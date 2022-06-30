<template>
  <div>
    <div class="filter users-filter mb-3">
      <div class="h4">{{ $t('userList.filterTitle') }}</div>
      <div class="row mb-2">
        <div class="col-12 col-md-3">
          <input-text-component
            name="name"
            autocomplete="off"
            v-model="filter.name"
            @update:modelValue="getData()"
            :placeholder="$t('userList.searchPlaceholder')"
          ></input-text-component>
        </div>
        <div class="col-12 col-md-3">
          <select-component
            :options="roles"
            :default-nothing-text="$t('userList.filterRoleSelect')"
            v-model="filter.role"
            :display-name="'display_name'"
            @update:modelValue="getData()"
          ></select-component>
        </div>
      </div>
      <div class="row">
        <div class="col-auto">
          <checkbox-component
            name="unconfirmed"
            label="Unconfirmed"
            v-model="filter.unconfirmed"
            @update:modelValue="getData()"
          ></checkbox-component>
        </div>
        <div class="col-auto">
          <checkbox-component
            name="has_ban"
            label="Has ban"
            v-model="filter.hasBan"
            @update:modelValue="getData()"
          ></checkbox-component>
        </div>
      </div>
    </div>

    <div class="mb-3">
      <a href="" class="btn btn-primary btn-with-icon">
        <ion-icon name="add-circle-outline"></ion-icon>
        <span>{{ $t('userList.create') }}</span>
      </a>
    </div>
    <table class="table responsive-table" v-if="!loading && users.data">
      <thead>
      <tr>
        <th scope="col" style="width: 58px;" class="border-end-0"></th>
        <th scope="col" class="border-start-0">#</th>
        <th scope="col">{{ $t('userList.login') }}</th>
        <th scope="col">{{ $t('userList.email') }}</th>
        <th scope="col">{{ $t('userList.name') }}</th>
        <th scope="col">{{ $t('userList.phone') }}</th>
        <th scope="col" style="width: 170px;">{{ $t('userList.createdAt') }}</th>
        <th scope="col" style="width: 170px;">{{ $t('userList.updatedAt') }}</th>
      </tr>
      </thead>
      <tbody>
      <!-- List of articles -->
      <tr v-if="users.data.length === 0">
        <td colspan="8" class="text-center">{{ $t('userList.emptyList') }}</td>
      </tr>
      <tr v-for="(user, index) in users.data" :key="index">
        <th scope="row" style="width: 40px;" class="border-end-0">
          <div class="dropdown">
            <button class="btn btn-sm" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <svg class="menu-icon">
                <use xlink:href="/themes/admin/assets/icons/sprite.svg#menu"></use>
              </svg>
            </button>
            <div class="dropdown-menu">
              <a class="dropdown-item" :href="user.editUrl">{{ $t('userList.edit') }}</a>
              <a class="dropdown-item" :data-url="user.deleteUrl" data-bs-toggle="modal" data-bs-target=".ajax_modal">{{ $t('userList.delete') }}</a>
            </div>
          </div>
        </th>
        <th scope="row" class="border-start-0"><a :href="user.editUrl">{{ user.id }}</a></th>
        <td :data-title="$t('userList.login')"><a :href="user.editUrl">{{ user.login }}</a></td>
        <td :data-title="$t('userList.email')"><a :href="user.editUrl">{{ user.email }}</a></td>
        <td :data-title="$t('userList.name')">{{ user.name }}</td>
        <td :data-title="$t('userList.phone')">{{ user.phone }}</td>
        <td :data-title="$t('userList.createdAt')">{{ user.createdAt }}</td>
        <td :data-title="$t('userList.updatedAt')">{{ user.updatedAt }}</td>
      </tr>
      </tbody>
    </table>
    <vue-pagination :data="users" @pagination-change-page="getData" class="mt-3"></vue-pagination>
  </div>
</template>

<script lang="ts">
import axios from "axios";
import InputTextComponent from "../../components/Forms/InputTextComponent.vue";
import VuePagination from "../../components/Pagination/VuePagination.vue";
import SelectComponent from "../../components/Forms/SelectComponent.vue";
import CheckboxComponent from "../../components/Forms/CheckboxComponent.vue";

export default {
  name: "UserList",
  components: {CheckboxComponent, SelectComponent, VuePagination, InputTextComponent},
  props: {
    listUrl: String,
    roles: {},
  },
  data() {
    return {
      loading: true,
      filter: {
        name: '',
        role: '',
        unconfirmed: false,
        hasBan: false,
      },
      users: {}
    };
  },
  mounted() {
    this.getData();
  },
  methods: {
    getData(page = 1) {
      this.loading = true;
      axios.get(this.listUrl, {
        params: {
          page: page,
          ...this.filter
        }
      })
        .then((response) => {
          this.users = response.data;
        })
        .catch(() => {
          console.log('error');
        })
        .finally(() => {
          this.loading = false;
        })
    }
  }
}
</script>

<style scoped>

</style>
