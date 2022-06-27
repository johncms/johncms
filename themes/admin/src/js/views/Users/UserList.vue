<template>
  <div>
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
  </div>
</template>

<script lang="ts">
import axios from "axios";

export default {
  name: "UserList",
  props: {
    listUrl: String,
  },
  data() {
    return {
      loading: true,
      users: {}
    };
  },
  mounted() {
    this.getData();
  },
  methods: {
    getData() {
      this.loading = true;
      axios.get(this.listUrl)
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
