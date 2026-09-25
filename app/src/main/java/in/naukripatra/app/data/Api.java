package in.naukripatra.app.data;

import android.content.Context;
import android.os.Handler;
import android.os.Looper;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;

import org.json.JSONObject;

import java.io.File;
import java.io.IOException;
import java.util.concurrent.TimeUnit;

import in.naukripatra.app.BuildConfig;
import okhttp3.Cache;
import okhttp3.CacheControl;
import okhttp3.Call;
import okhttp3.Callback;
import okhttp3.HttpUrl;
import okhttp3.Interceptor;
import okhttp3.OkHttpClient;
import okhttp3.Request;
import okhttp3.Response;
import okhttp3.ResponseBody;

/**
 * Single HTTP client for the naukripatra.in REST API.
 *
 * - Responses are cached on disk for a short time, so going back and forth between
 *   screens is instant and the last data is still shown when the phone is offline.
 * - The server occasionally resets connections under load, so failed requests are
 *   retried twice with a short back-off before the user sees an error.
 * - JSON is parsed on the OkHttp worker thread; callbacks run on the main thread.
 */
public final class Api {

    public static final String SITE = "https://naukripatra.in/";
    private static final String BASE = SITE + "wp-json/naukripatra/v2/";

    private static volatile OkHttpClient client;
    private static final Handler MAIN = new Handler(Looper.getMainLooper());

    public interface Parser<T> {
        T parse(JSONObject json) throws Exception;
    }

    public interface Result<T> {
        void onSuccess(T data);

        void onError(@NonNull String message);
    }

    private Api() {
    }

    public static void init(Context context) {
        if (client != null) return;
        synchronized (Api.class) {
            if (client != null) return;
            String agent = System.getProperty("http.agent");
            final String userAgent = (agent == null ? "Android" : agent)
                    + " NaukriPatra/" + BuildConfig.VERSION_NAME;

            Interceptor headers = chain -> chain.proceed(chain.request().newBuilder()
                    .header("User-Agent", userAgent)
                    .header("Accept", "application/json")
                    .build());

            // WordPress sends no-cache headers; keep each response for 2 minutes.
            Interceptor cacheFor = chain -> {
                Response response = chain.proceed(chain.request());
                return response.newBuilder()
                        .removeHeader("Pragma")
                        .header("Cache-Control", "public, max-age=120")
                        .build();
            };

            client = new OkHttpClient.Builder()
                    .cache(new Cache(new File(context.getCacheDir(), "http"), 20L * 1024 * 1024))
                    .connectTimeout(15, TimeUnit.SECONDS)
                    .readTimeout(25, TimeUnit.SECONDS)
                    .retryOnConnectionFailure(true)
                    .addInterceptor(headers)
                    .addNetworkInterceptor(cacheFor)
                    .build();
        }
    }

    public static OkHttpClient client() {
        return client;
    }

    // ============ Endpoints ============

    public static HttpUrl.Builder endpoint(String path) {
        HttpUrl url = HttpUrl.parse(BASE + path);
        if (url == null) throw new IllegalArgumentException(path);
        return url.newBuilder();
    }

    public static String jobs(JobQuery q, int page, int limit) {
        HttpUrl.Builder b;
        switch (q.type) {
            case SEARCH:
                b = endpoint("search").addQueryParameter("q", q.value);
                break;
            case STATE:
                b = endpoint("state-wise-jobs").addQueryParameter("state", q.value);
                break;
            case QUALIFICATION:
                b = endpoint("qualification-wise-jobs").addQueryParameter("qualification", q.value);
                break;
            case ALL_INDIA:
                b = endpoint("all-india-jobs");
                break;
            case USA:
                b = endpoint("usa-jobs");
                break;
            case LATEST:
            default:
                b = endpoint("latest-jobs");
                break;
        }
        return b.addQueryParameter("page", String.valueOf(page))
                .addQueryParameter("limit", String.valueOf(limit))
                .build().toString();
    }

    public static String post(int id) {
        return BASE + "post/" + id;
    }

    public static String related(int id) {
        return BASE + "related-posts/" + id;
    }

    /** Standard WordPress endpoint, used to turn a notification URL slug into a post id. */
    public static String postIdBySlug(String slug) {
        HttpUrl url = HttpUrl.parse(SITE + "wp-json/wp/v2/posts");
        return url.newBuilder()
                .addQueryParameter("slug", slug)
                .addQueryParameter("_fields", "id")
                .build().toString();
    }

    // ============ Requests ============

    /** Fires a GET request. Returns the call so screens can cancel it. */
    public static <T> Call get(String url, Parser<T> parser, Result<T> result) {
        return get(url, false, parser, result);
    }

    /** @param fresh skip the short-lived cache (pull-to-refresh). */
    public static <T> Call get(String url, boolean fresh, Parser<T> parser, Result<T> result) {
        Request.Builder builder = new Request.Builder().url(url);
        if (fresh) builder.cacheControl(CacheControl.FORCE_NETWORK);
        Request request = builder.build();
        Call call = client.newCall(request);
        call.enqueue(new RetryingCallback<>(request, parser, result, 0));
        return call;
    }

    /** GET that returns the raw body, for endpoints whose root is a JSON array. */
    public static Call getRaw(String url, Result<String> result) {
        Request request = new Request.Builder().url(url).build();
        Call call = client.newCall(request);
        call.enqueue(new Callback() {
            @Override
            public void onFailure(@NonNull Call c, @NonNull IOException e) {
                post(() -> result.onError(friendly(e)));
            }

            @Override
            public void onResponse(@NonNull Call c, @NonNull Response response) {
                try (ResponseBody body = response.body()) {
                    String text = body == null ? "" : body.string();
                    if (!response.isSuccessful()) throw new IOException("HTTP " + response.code());
                    post(() -> result.onSuccess(text));
                } catch (Exception e) {
                    post(() -> result.onError(friendly(e)));
                }
            }
        });
        return call;
    }

    private static final class RetryingCallback<T> implements Callback {
        private static final int MAX_RETRIES = 2;
        private final Request request;
        private final Parser<T> parser;
        private final Result<T> result;
        private final int attempt;

        RetryingCallback(Request request, Parser<T> parser, Result<T> result, int attempt) {
            this.request = request;
            this.parser = parser;
            this.result = result;
            this.attempt = attempt;
        }

        @Override
        public void onFailure(@NonNull Call call, @NonNull IOException e) {
            if (call.isCanceled()) return;
            if (attempt < MAX_RETRIES) {
                MAIN.postDelayed(() -> client.newCall(request)
                                .enqueue(new RetryingCallback<>(request, parser, result, attempt + 1)),
                        700L * (attempt + 1));
                return;
            }
            // Out of retries: fall back to whatever is cached, even if stale.
            Request cached = request.newBuilder().cacheControl(CacheControl.FORCE_CACHE).build();
            client.newCall(cached).enqueue(new Callback() {
                @Override
                public void onFailure(@NonNull Call c, @NonNull IOException ignored) {
                    post(() -> result.onError(friendly(e)));
                }

                @Override
                public void onResponse(@NonNull Call c, @NonNull Response response) {
                    if (response.code() == 504) { // not in cache
                        response.close();
                        post(() -> result.onError(friendly(e)));
                        return;
                    }
                    deliver(response, parser, result);
                }
            });
        }

        @Override
        public void onResponse(@NonNull Call call, @NonNull Response response) {
            if (!response.isSuccessful() && response.code() >= 500 && attempt < MAX_RETRIES) {
                response.close();
                onFailure(call, new IOException("HTTP " + response.code()));
                return;
            }
            deliver(response, parser, result);
        }
    }

    private static <T> void deliver(Response response, Parser<T> parser, Result<T> result) {
        try (ResponseBody body = response.body()) {
            if (!response.isSuccessful() || body == null) {
                throw new IOException("HTTP " + response.code());
            }
            JSONObject json = new JSONObject(body.string());
            final T data = parser.parse(json);
            post(() -> result.onSuccess(data));
        } catch (Exception e) {
            post(() -> result.onError(friendly(e)));
        }
    }

    private static void post(Runnable r) {
        MAIN.post(r);
    }

    private static String friendly(@Nullable Exception e) {
        if (e instanceof java.net.UnknownHostException
                || e instanceof java.net.ConnectException
                || e instanceof java.net.SocketTimeoutException) {
            return "No internet connection. Please check your network and try again.";
        }
        return "Something went wrong while loading. Please try again.";
    }
}
