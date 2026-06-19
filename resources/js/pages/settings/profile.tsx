import { Form, Head, Link, usePage } from '@inertiajs/react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/delete-user';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';

export default function Profile({
    mustVerifyEmail,
    status,
}: {
    mustVerifyEmail: boolean;
    status?: string;
}) {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Profile settings" />

            <h1 className="sr-only">Profile settings</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Profile information"
                    description="Update your profile details and settings"
                />

                <div className="grid gap-4 rounded-xl border p-4 bg-muted/40">
                    <h3 className="font-semibold text-sm">Account Status & Details</h3>
                    <div className="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                        <div>
                            <span className="text-muted-foreground block text-xs">KYC Verification</span>
                            <span className="font-medium capitalize">{auth.user.kyc_level === 0 || !auth.user.kyc_level ? 'Level 0 (Unverified)' : `Level ${auth.user.kyc_level}`}</span>
                        </div>
                        <div>
                            <span className="text-muted-foreground block text-xs">Account Status</span>
                            <span className="font-medium capitalize">{auth.user.status || 'Active'}</span>
                        </div>
                        <div>
                            <span className="text-muted-foreground block text-xs">Joined On</span>
                            <span className="font-medium">
                                {auth.user.created_at ? new Date(auth.user.created_at).toLocaleDateString(undefined, {
                                    year: 'numeric',
                                    month: 'long',
                                    day: 'numeric'
                                }) : 'N/A'}
                            </span>
                        </div>
                    </div>
                </div>

                <Form
                    {...ProfileController.update.form()}
                    options={{
                        preserveScroll: true,
                    }}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>

                                <Input
                                    id="name"
                                    className="mt-1 block w-full"
                                    defaultValue={auth.user.name}
                                    name="name"
                                    required
                                    autoComplete="name"
                                    placeholder="Full name"
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.name}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email address</Label>

                                <Input
                                    id="email"
                                    type="email"
                                    className="mt-1 block w-full"
                                    defaultValue={auth.user.email}
                                    name="email"
                                    required
                                    autoComplete="username"
                                    placeholder="Email address"
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.email}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="phone">Phone Number</Label>

                                <Input
                                    id="phone"
                                    type="text"
                                    className="mt-1 block w-full"
                                    defaultValue={auth.user.phone || ''}
                                    name="phone"
                                    placeholder="Phone number"
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.phone}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="nationality">Nationality</Label>

                                <Input
                                    id="nationality"
                                    type="text"
                                    className="mt-1 block w-full"
                                    defaultValue={auth.user.nationality || ''}
                                    name="nationality"
                                    placeholder="Nationality"
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.nationality}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="date_of_birth">Date of Birth</Label>

                                <Input
                                    id="date_of_birth"
                                    type="date"
                                    className="mt-1 block w-full"
                                    defaultValue={auth.user.date_of_birth ? auth.user.date_of_birth.split('T')[0] : ''}
                                    name="date_of_birth"
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.date_of_birth}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="country_of_residence">Country of Residence</Label>

                                <Input
                                    id="country_of_residence"
                                    type="text"
                                    className="mt-1 block w-full"
                                    defaultValue={auth.user.country_of_residence || ''}
                                    name="country_of_residence"
                                    placeholder="Country of residence"
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.country_of_residence}
                                />
                            </div>

                            {mustVerifyEmail &&
                                auth.user.email_verified_at === null && (
                                    <div>
                                        <p className="-mt-4 text-sm text-muted-foreground">
                                            Your email address is unverified.{' '}
                                            <Link
                                                href={send()}
                                                as="button"
                                                className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                                            >
                                                Click here to resend the
                                                verification email.
                                            </Link>
                                        </p>

                                        {status ===
                                            'verification-link-sent' && (
                                            <div className="mt-2 text-sm font-medium text-green-600">
                                                A new verification link has been
                                                sent to your email address.
                                            </div>
                                        )}
                                    </div>
                                )}

                            <div className="flex items-center gap-4">
                                <Button
                                    disabled={processing}
                                    data-test="update-profile-button"
                                >
                                    Save
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>

            <DeleteUser />
        </>
    );
}

Profile.layout = {
    breadcrumbs: [
        {
            title: 'Profile settings',
            href: edit(),
        },
    ],
};
