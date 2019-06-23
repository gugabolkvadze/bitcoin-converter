<template>
    <v-app>
        <v-container>
            <v-card class="w-100">
                <v-card-title><h2>Converter calculator</h2></v-card-title>
                <v-card-text>
                    <v-layout>
                        <v-flex md4>
                            <v-text-field
                                v-model="converter.currency_btc"
                                label="BTC"
                                type="number"
                                placeholder="0.00"
                            ></v-text-field>
                        </v-flex>
                        <v-flex md4 class="text-center">
                            <v-btn fab color="primary"
                                   @click="storeTransactionHistory"
                                   :loading="converter.loading"
                                   :disabled="converter.loading"
                            >
                                <v-icon>autorenew</v-icon>
                            </v-btn>
                        </v-flex>
                        <v-flex md4>
                            <v-text-field
                                v-model="converter.currency_gel"
                                label="GEL"
                                type="number"
                                placeholder="0.00"
                            ></v-text-field>
                        </v-flex>
                    </v-layout>
                </v-card-text>
            </v-card>
            <v-card class="mt-4">
                <v-card>
                    <v-card-title><h2>Transaction history</h2></v-card-title>
                    <v-card-text>
                        <v-data-table
                            :headers="transaction_history.headers"
                            :items="transaction_history.items"
                            :pagination.sync="transaction_history.pagination"
                            :total-items="transaction_history.totalItems"
                            :loading="transaction_history.loading"
                        >
                            <template slot="items" slot-scope="props">
                                <td>{{ props.item.id }}</td>
                                <td>{{ 'test' }}</td>
                                <td>{{ formatDate(props.item.created_at) }}</td>
                                <td>{{ props.item.rate }}</td>
                            </template>
                        </v-data-table>
                    </v-card-text>
                </v-card>
            </v-card>
        </v-container>
    </v-app>
</template>

<script>
    import axios from 'axios'
    import moment from 'moment'
    const api = axios.create({
        baseURL: process.env.MIX_API_URL
    })

    export default {
        data() {
            return {
                converter: {
                    loading: false,
                    currency_btc: null,
                    currency_gel: null,
                    is_currency_gel_updating: true
                },
                transaction_history: {
                    totalItems: 0,
                    items: [],
                    loading: true,
                    pagination: {},
                    headers: [
                        {
                            text: 'ID',
                            align: 'left',
                            sortable: false,
                            value: 'id'
                        },
                        {text: 'Name', value: 'name'},
                        {text: 'Date', value: 'created_at'},
                        {text: 'Coins', value: 'rate'}
                    ]
                }
            }
        },
        watch: {
            'transaction_history.pagination': {
                async handler () {
                    const data = await this.fetchTransactionHistory()
                    this.transaction_history.items = data.items
                    this.transaction_history.totalItems = data.total
                },
                deep: true,
                immediate: true
            },
            'converter.currency_btc' () {
                this.converter.is_currency_gel_updating = true
            },
            'converter.currency_gel' () {
                this.converter.is_currency_gel_updating = false
            }
        },
        methods: {
            async fetchTransactionHistory () {
                try {
                    const { sortBy, descending, page, rowsPerPage } = this.transaction_history.pagination

                    this.transaction_history.loading = true

                    const resp = await api.get('transaction_histories', {
                        params: {
                            page,
                            per_page: rowsPerPage
                        }
                    })

                    let items = resp.data.data
                    const total = resp.data.meta.total

                    if (this.transaction_history.pagination.sortBy) {
                        items = items.sort((a, b) => {
                            const sortA = a[sortBy]
                            const sortB = b[sortBy]

                            if (descending) {
                                if (sortA < sortB) return 1
                                if (sortA > sortB) return -1
                                return 0
                            } else {
                                if (sortA < sortB) return -1
                                if (sortA > sortB) return 1
                                return 0
                            }
                        })
                    }

                    return {
                        items,
                        total
                    }
                } finally {
                    this.transaction_history.loading = false
                }
            },
            async storeTransactionHistory () {
                try {
                    this.converter.loading = true
                    const resp = await api.post('transaction_histories')
                    this.transaction_history.items.unshift(resp.data.data)

                    const rate = resp.data.data.rate
                    if (this.converter.is_currency_gel_updating) {
                        this.converter.currency_gel = this.converter.currency_btc / rate
                    } else {
                        this.converter.currency_btc = this.converter.currency_gel * rate
                    }
                } finally {
                    this.converter.loading = false
                }
            },
            formatDate (date) {
                return moment.utc(date).local().format('lll')
            }
        }
    }
</script>
