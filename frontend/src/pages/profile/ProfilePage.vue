<template>
    <div class="max-w-4xl mx-auto p-6">
        <h1 class="text-3xl font-bold mb-8">My Profile</h1>

        <div v-if="authStore.currentUser" class="space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle>Profile Photo</CardTitle>
                    <CardDescription>Update your profile picture</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="flex flex-col sm:flex-row items-start gap-6">
                        <div class="flex-shrink-0">
                            <Avatar class="w-32 h-32">
                                <AvatarImage v-if="authStore.currentUser.photo_url"
                                    :src="`${serverBaseURL}/storage/photos/${authStore.currentUser.photo_url}`"
                                    :alt="authStore.currentUser.name" />
                                <AvatarFallback class="text-4xl">
                                    {{ authStore.currentUser.name?.charAt(0).toUpperCase() }}
                                </AvatarFallback>
                            </Avatar>
                        </div>

                        <div class="flex-1 space-y-3">
                            <div class="flex flex-wrap gap-2">
                                <Button @click="open" variant="outline">
                                    Choose Photo
                                </Button>
                                <Button v-if="files" @click="uploadPhoto">Save Photo</Button>
                                <Button v-if="files" @click="reset" variant="ghost">
                                    Cancel
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Account Information</CardTitle>
                    <CardDescription>Update your personal details</CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="space-y-2">
                        <Label for="name">Name</Label>
                        <Input id="name" v-model="formData.name" placeholder="Enter your name" />
                    </div>
                    <div class="space-y-2">
                        <Label for="email">Email</Label>
                        <Input id="email" v-model="formData.email" type="email" placeholder="Enter your email" />
                    </div>
                </CardContent>
                <CardFooter class="flex justify-between">
                    <Button @click="saveProfile"> Save Changes </Button>
                </CardFooter>
            </Card>
        </div>
    </div>
</template>

<script setup>
import { ref, inject, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useAPIStore } from '@/stores/api'
import { useFileDialog } from '@vueuse/core'
import { toast } from 'vue-sonner'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'

const authStore = useAuthStore()
const apiStore = useAPIStore()

const serverBaseURL = inject("serverBaseURL")

const formData = ref({
    name: '',
    email: ''
})

watch(() => authStore.currentUser, (user) => {
    if (user) {
        formData.value = {
            name: user.name || '',
            email: user.email || ''
        }
    }
}, { immediate: true })

const { files, open, reset } = useFileDialog({
    accept: 'image/*',
    multiple: false
})


const uploadPhoto = async () => {

    try {
        const response = await apiStore.uploadProfilePhoto(files.value[0])

        if (response.data && response.data.photo_url) {
            await apiStore.patchUserPhoto(authStore.currentUser.id, response.data.photo_url)
            await authStore.getUser()

            toast.success("Profile photo updated successfully")

            reset()
        }
    } catch (error) {
        console.error('Failed to upload photo:', error)
        toast.success("Failed to upload photo. Please try again.")
    }
}

const saveProfile = async () => {

    try {

        const user = Object.assign({}, authStore.currentUser)

        user.name = formData.value.name
        user.email = formData.value.email

        await apiStore.putUser(user)
        await authStore.getUser()
        toast.success("Profile updated successfully")

    } catch (error) {
        console.error('Failed to update profile:', error)
        toast.error("Failed to update profile. Please try again.")
    }
}
</script>
